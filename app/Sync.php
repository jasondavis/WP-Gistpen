<?php
namespace Intraxia\Gistpen;

use Github\Client;
use Intraxia\Gistpen\Client\Gist;
use Intraxia\Gistpen\Facade\Adapter;
use Intraxia\Gistpen\Facade\Database;
use Intraxia\Gistpen\Model\Commit\Meta;
use Intraxia\Gistpen\Model\Zip;
use Intraxia\Jaxion\Contract\Core\HasActions;

/**
 * Manages the data to keep the database in sync with Gist.
 *
 * @package    Intraxia\Gistpen
 * @author     James DiGioia <jamesorodig@gmail.com>
 * @link       http://jamesdigioia.com/wp-gistpen/
 * @since      0.5.0
 */
class Sync implements HasActions {
	/**
	 * Database Facade object
	 *
	 * @var Database
	 * @since 0.5.0
	 */
	protected $database;

	/**
	 * Adapter Facade object
	 *
	 * @var Adapter
	 * @since  0.5.0
	 */
	protected $adapter;

	/**
	 * Gist Account object
	 *
	 * @var    Gist
	 * @since  0.5.0
	 */
	public $gist;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.5.0
	 *
	 * @param Database $database
	 * @param Adapter  $adapter
	 */
	public function __construct( Database $database, Adapter $adapter ) {
		$this->database = $database;
		$this->adapter  = $adapter;

		$this->gist = new Gist( $adapter, new Client );
	}

	/**
	 * Exports a Gistpen to Gist based on its ID
	 *
	 * If the Zip doesn't have a Gist ID, create a new Gist,
	 * or update an existing Gist if it does.
	 *
	 * @param Zip $zip
	 *
	 * @return Zip
	 *
	 */
	public function export_gistpen( Zip $zip ) {
		if ( false === cmb2_get_option( 'wp-gistpen', '_wpgp_gist_token' ) ) {
			return $zip;
		}

		/** @var Meta $commit */
		$commit = $this->database->query( 'commit' )->latest_by_head_id( $zip->get_ID() );

		if ( 'on' !== $commit->get_sync() || 'none' !== $commit->get_gist_id() ) {
			return $zip;
		}

		$result = 'none' === $commit->get_head_gist_id() ? $this->create_gist( $commit ) : $this->update_gist( $commit );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $zip;
	}

	/**
	 * Creates a new Gistpen on Gist
	 *
	 * @param Meta $commit
	 *
	 * @return string|\WP_Error
	 * @since 0.5.0
	 */
	protected function create_gist( Meta $commit ) {
		$response = $this->gist->create( $commit );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$result = $this->database->persist( 'head' )->set_gist_id( $commit->get_head_id(), $response['id'] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result = $this->database->persist( 'commit' )->set_gist_id( $commit->get_ID(), $response['history'][0]['version'] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $response;
	}

	/**
	 * Updates an existing Gistpen on Gist
	 *
	 * @param Meta $commit
	 *
	 * @return string|\WP_Error Gist ID on success, WP_Error on failure
	 * @since 0.5.0
	 */
	protected function update_gist( Meta $commit ) {
		$result = $this->gist->update( $commit );

		if ( ! $result ) {
			return $this->gist->get_error();
		}

		$result = $this->database->persist( 'commit' )->set_gist_id( $commit->get_ID(), $result['history'][0]['version'] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $result;
	}

	/**
	 * Imports a Gist into Gistpen by ID
	 *
	 * @param  string $gist_id Gist ID
	 *
	 * @return string|\WP_Error               Gist ID on success, WP_Error on failure
	 * @since  0.5.0
	 */
	public function import_gist( $gist_id ) {
		// Exit if this gist has already been imported
		$query = $this->database->query( 'head' )->by_gist_id( $gist_id );

		if ( $query instanceof Zip ) {
			return $query;
		}

		$response = $this->gist->get( $gist_id );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$zip     = $response['zip'];
		$version = $response['version'];
		unset( $response );

		$result = $this->database->persist( 'head' )->by_zip( $zip );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$ids = $result;
		unset( $result );

		$result = $this->database->persist( 'commit' )->by_ids( $ids );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result = $this->database->persist( 'commit' )->set_gist_id( $result, $version );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $ids['zip'];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array[]
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'   => 'wpgp_zip_created',
				'method' => 'export_gistpen',
			),
		);
	}
}
