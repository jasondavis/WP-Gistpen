var Backbone = require('backbone');
var _ = require('underscore');
var $ = require('jquery');

module.exports = Backbone.View.extend({
    /**
     * Backbone view events.
     */
    events: {
        'change [data-wpgp-theme]': 'handleThemeChange',
        'click [data-wpgp-add]': 'handleAddClick',
        'click [data-wpgp-update]': 'handleUpdateClick',
        'change [data-wpgp-invisibles]': 'handleInvisiblesChange',
        'change [data-wpgp-tabs]': 'handleTabsChange',
        'change [data-wpgp-width]': 'handleWidthChange'
    },

    /**
     * Compiled mustache template.
     */
    template: require('./template.hbs'),

    /**
     * Renders the view to the DOM.
     */
    render: function() {
        var data = _.extend({}, this.model.user.toJSON(), Gistpen_Settings);
        data.tabs = data['ace_tabs'] === 'on';
        data.invisibles = data['ace_invisibles'] === 'on';
        this.setElement($(this.template(data)));

        var theme = this.model.user.get('ace_theme');

        if (theme) {
            this.$('[data-wpgp-theme]').val(theme);
        } else {
            this.model.user.save({'ace_theme': this.$('[data-wpgp-theme]').val()}, {patch: true});
        }

        var width = this.model.user.get('ace_width');

        if (width) {
            this.$('[data-wpgp-width]').val(width);
        } else {
            this.model.user.save({'ace_width': this.$('[data-wpgp-width]').val()}, {patch: true});
        }

        this.$spinner = this.$('[data-wpgp-spinner]');

        return this;
    },

    /**
     * Updates the user's theme when the input value changes.
     */
    handleThemeChange: function (event) {
        this.model.user.save({'ace_theme': event.target.value}, {patch: true});
    },

    /**
     * Emits an event to all listeners when the add button is clicked.
     */
    handleAddClick: function(e) {
        e.preventDefault();

        this.trigger('click:add', this);
    },

    /**
     * Emits an event to all listeners when the update button is clicked.
     */
    handleUpdateClick: function (e) {
        e.preventDefault();

        this.trigger('click:update', this);
    },

    /**
     * Update the ace editor's tab state.
     */
    handleInvisiblesChange: function (event) {
        this.model.user.save({'ace_invisibles': event.target.checked ? 'on' : 'off'}, {patch: true});
    },

    /**
     * Update the ace editor's tab state.
     */
    handleTabsChange: function (event) {
        this.model.user.save({'ace_tabs': event.target.checked ? 'on' : 'off'}, {patch: true});
    },

    /**
     * Update the ace editor's tab state.
     */
    handleWidthChange: function (event) {
        this.model.user.save({'ace_width': parseInt(event.target.value, 10)}, {patch: true});
    },

    /**
     * Adds a class to show the spinner gif.
     */
    enableSpinner: function () {
        this.$spinner.addClass('is-active');
    },

    /**
     * Removes a class to hide the spinner gif.
     */
    disableSpinner: function () {
        this.$spinner.removeClass('is-active');
    }
});
