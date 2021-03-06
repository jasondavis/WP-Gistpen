<?php
namespace Intraxia\Gistpen\Model;

use Intraxia\Gistpen\App;

/**
 * Manages the Gistpen's file data
 *
 * @package    Intraxia\Gistpen
 * @author     James DiGioia <jamesorodig@gmail.com>
 * @link       http://jamesdigioia.com/wp-gistpen/
 * @since      0.5.0
 */
class File {

	/**
	 * File's slug
	 *
	 * @var string
	 * @since  0.4.0
	 */
	protected $slug = '';

	/**
	 * File's raw code
	 *
	 * @var string
	 * @since 0.4.0
	 */
	protected $code = '';

	/**
	 * File's ID
	 * @var int
	 * @since 0.4.0
	 */
	protected $ID = null;

	/**
	 * File's language object
	 *
	 * @var Language
	 * @since 0.4.0
	 */
	protected $language;

	/**
	 * Lines to highlight in shortcode
	 *
	 * @var string
	 * @since 0.4.0
	 */
	protected $highlight = null;

	/**
	 * Get the file's slug
	 *
	 * @since  0.5.0
	 * @return string File slug
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Set the file's slug
	 *
	 * @since  0.5.0
	 * @param string $slug File slug
	 */
	public function set_slug( $slug ) {
		$this->slug = strtolower( str_replace( ' ', '-', $slug ) );
	}

	/**
	 * Get the file's code
	 *
	 * @since  0.5.0
	 * @return string File code
	 */
	public function get_code() {
		return $this->code;
	}

	/**
	 * Set the file's slug
	 *
	 * @since  0.5.0
	 * @param string $code File code
	 */
	public function set_code( $code ) {
		$this->code = $code;
	}

	/**
	 * Get the file's DB ID
	 *
	 * @since  0.4.0
	 * @return int File's db ID
	 */
	public function get_ID() {
		return $this->ID;
	}

	/**
	 * Set the file's DB ID as integer
	 *
	 * @since  0.5.0
	 * @param  int $ID DB id
	 */
	public function set_ID( $ID ) {
		if ( $ID ) {
			$this->ID = (int) $ID;
		}
	}

	/**
	 * Get the file's language
	 *
	 * @since  0.5.0
	 * @return null|Language
	 */
	public function get_language() {
		if ( ! isset( $this->language) ) {
			return null;
		}

		return $this->language;
	}

	/**
	 * Set the file's slug
	 *
	 * @since  0.5.0
	 * @param Language $language File language
	 */
	public function set_language( Language $language ) {
		$this->language = $language;
	}

	/**
	 * Get the file's filename with file extension
	 *
	 * @since  0.4.0
	 * @return string filename w/ ext
	 */
	public function get_filename() {
		// Do we want to readd file ext as an option?
		// Note that we currently can't export Gists with language data.
		// The file extension is the only we get that data in properly.
		return $this->slug; // . '.' . $this->language->get_file_ext();
	}

	/**
	 * Get's the file's post content for display
	 * on the front-end
	 *
	 * @return string File's post content
	 * @since 0.4.0
	 */
	public function get_post_content() {
		$post_content = '<pre class="gistpen';

		$prism = App::instance()->fetch( 'options.site' )->get( 'prism' ); // @todo this whole section should be in a view

		if ( $prism['line-numbers'] ) {
			$post_content .= ' line-numbers';
		}

		$post_content .= '"';

		// Line highlighting and offset will go here
		if ( $this->highlight !== null ) {
			$post_content .= ' data-line="' . $this->highlight . '"';
		}

		$file_post = get_post( $this->get_ID() );

		if ( $edit_url = get_edit_post_link( $file_post->post_parent, '' ) ) {
			$post_content .= ' data-edit-url="' . $edit_url . '"';
		}

		$post_content .= ' data-filename="' . $this->get_filename() . '">';
		$post_content .= '<code class="language-' . $this->language->get_prism_slug() . '">' . htmlentities( $this->code );
		$post_content .= '</code></pre>';

		return $post_content;

	}

	/**
	 * Get's the file's shortcode content for display
	 * on the front-end
	 *
	 * @return string File's post content
	 * @since 0.4.0
	 */
	public function get_shortcode_content( $highlight = null ) {
		$this->highlight = $highlight;

		return $this->get_post_content();
	}

}
