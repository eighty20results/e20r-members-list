/*
 * License:

	Copyright 2016-2021 - Eighty / 20 Results by Wicked Strong Chicks, LLC (thomas@eighty20results.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

(function ($) {
    'use strict';
    var e20rMembersList_Page = {
        init: function () {

            this.memberslist_form = $('.e20r-pmpro-memberslist-page form#posts-filter');
            // this.levels_dropdown = $('select#e20r-pmpro-memberslist-levels');
            this.edit_lnk = $('a.e20r-members-list-editable');
            // this.enddate_lnk = $('a.e20r-members-list_enddate');
            this.resetBtn = $('a.e20r-members-list-cancel');
            this.cancelMemberLnk = $('a.e20r-cancel-member');
            this.updateBtn = $('a.e20r-members-list-save');
            this.updateMemberLnk = $('a.e20r-update-member');
            this.exportBtn = $('a.e20r-memberslist-export');
            this.changed_input = $('input[class^="e20r-members-list-input-"]');
            this.changed_select = $( 'select[class^="e20r-members-list-select-"]');
            this.bulkUpdate = $('#doaction, #doaction2');
            this.updateListBtn = $('#e20r-update-list');

            this.dateFields = $('.e20r-members-list-input-enddate, .e20r-members-list-input-startdate');

            this.bulkActionSelectTop = $('select#bulk-action-selector-top');
            this.bulkActionSelectBottom = $('select#bulk-action-selector-bottom');

            this.dateOpts = {
                year: '2-digit', month: 'short',
                day: 'numeric'
            };

            var self = this;

            self.dateFields.datepicker({
                dateFormat: "yy-mm-dd"
            });

            self.changed_input.unbind('blur').on('blur', function(ev) {
                self.set_update( this );
            });

            self.updateListBtn.unbind('click').on('click', function(ev) {

                if ( 'Clear Search' === self.updateListBtn.val() ) {
                    window.console.log(e20rml.url);
                    location.href = e20rml.url;
                }

                $('#post-search-input').val(null);
            });
            /*
            self.changed_select.unbind('blur').on('blur', function() {
                self.set_update( this );
            });
            */

            self.changed_select.unbind('change').on('change', function() {

                var current_select = $(this);
                var current_select_info = current_select.val();
                var $field_name = current_select.closest('div.ml-row-settings').find('.e20r-members-list-field-name').val();
                var previous_select_info = current_select.closest('div.ml-row-settings').find('input.e20r-members-list-db_' + $field_name ).val();
                var enddate = $('#the-list').find('.last .e20r-members-list-db-enddate').val();

                window.console.log("Previous value: " + previous_select_info );
                window.console.log("New value: " + current_select_info );

                if ( previous_select_info !== current_select_info && '' !== enddate ) {
                    if ( ! window.confirm(e20rml.lang.clearing_enddate) ) {
                        return false;
                    }
                }

                self.set_update( this );
            });

            self.bulkUpdate.unbind('click').on('click', function (ev) {

                var button = $(this);
                ev.preventDefault();

                if ('bulk-export' === self.bulkActionSelectTop.val() || 'bulk-export' === self.bulkActionSelectBottom.val()) {
                    var export_args = {};
                    var inputs = $('.e20r-search-arguments input, .e20r-search-arguments textarea, .e20r-search-arguments select')
                        .not(':input[type=button], :input[type=submit], input[type=reset]');

                    export_args.action = "e20rml_export_records";
                    export_args._wpnonce = $('#_wpnonce').val();
                    export_args.showDebugTrace = true;
                    // export_args.showDebugArgs = true;

                    inputs.each(function () {

                        var input = $(this);
                        var name = input.attr('name');
                        var value = input.val();

                        if (false === self.is_empty(value)) {
                            window.console.log(name + " contains " + value);

                            export_args[name] = value;
                        }
                    });

                    var is_checked = false;
                    var selected_ids = [];

                    $('input[name^="member_id"]').each(function () {

                        if ($(this).is(':checked')) {
                            is_checked = true;
                            selected_ids.push($(this).val());
                        }
                    });

                    if (selected_ids.length > 0) {
                        export_args.member_id = selected_ids;
                    }

                    self.submit_export(this, export_args);
                    return true;
                }

                self.memberslist_form.submit();
            });

            self.updateMemberLnk.unbind('click').on('click', function (ev) {

                ev.preventDefault();
                var btn = $(this);
                var row = btn.closest('tr');

                var membership_col = row.find('td.column-membership');
                var membership_settings = membership_col.find('div.ml-row-settings');
                var membership_label = membership_col.find('a.e20r-members-list-editable');

                var startdate_col = row.find('td.column-startdate');
                var startdate_settings = startdate_col.find('div.ml-row-settings');
                var startdate_label = startdate_col.find('a.e20r-members-list-editable');

                var enddate_col = row.find('td.column-last');
                var enddate_settings = enddate_col.find('div.ml-row-settings');
                var enddate_label = enddate_col.find('a.e20r-members-list-editable');

                var status_col = row.find( 'td.column-status');
                var status_settings = status_col.find('div.ml-row-settings');
                var status_label = status_col.find('a.e20r-members-list-editable');

                membership_label.toggle();
                membership_settings.toggle();

                status_label.toggle();
                status_settings.toggle();

                $('.column-joindate').each(function () {
                    $(this).toggle();
                });

                startdate_label.toggle();
                startdate_settings.toggle();

                enddate_label.toggle();
                enddate_settings.toggle();
            });

            // Process edit link
            self.edit_lnk.unbind('click').on('click', function ($event) {

                $event.preventDefault();

                var $edit = $(this);
                var $input = $edit.next('div.ml-row-settings');

                $input.toggle();
                $edit.toggle();

            });

            /**
             * Cancel membership link handler
             */
            self.resetBtn.unbind('click').on('click', function (ev) {

                ev.preventDefault();

                var $btn = $(this);
                var $settings = $btn.closest('div.ml-row-settings');
                var $label = $settings.prev('a.e20r-members-list-editable');
                var $field_name = $settings.find('.e20r-members-list-field-name').val();

                // Return the select value (or input value) to its original value...
                var $original = $settings.find('.e20r-members-list-db-' + $field_name ).val();
                var $field_input_key = 'e20r-members-list-new_' + $field_name;

                $settings.find('select[name^="' + $field_input_key + '"], input[name^="'+ $field_input_key + '"]').val( $original );

                $settings.toggle();
                $label.toggle();
            });

            self.exportBtn.unbind('click').on('click', function (ev) {

                ev.preventDefault();
                window.console.log("Export button clicked!");
                var export_args = {};
                var inputs = $('.e20r-search-arguments input, .e20r-search-arguments textarea, .e20r-search-arguments select')
                    .not(':input[type=button], :input[type=submit], input[type=reset]');

                export_args.action = "e20rml_export_records";
                export_args._wpnonce = $('#_wpnonce').val();
                export_args.showDebugTrace = true;

                inputs.each(function () {

                    var input = $(this);
                    var name = input.attr('name');
                    var value = input.val();

                    if (false === self.is_empty(value)) {
                        window.console.log(name + " contains " + value);

                        export_args[name] = value;
                    }
                });

                var is_checked = false;
                var selected_ids = [];

                $('input[name^="member_id"]').each(function () {

                    if ($(this).is(':checked')) {
                        is_checked = true;
                        selected_ids.push($(this).val());
                    }
                });

                if (selected_ids.length > 0) {
                    export_args.member_id = selected_ids;
                }

                self.submit_export(this, export_args);
            });

            /*
            self.updateBtn.unbind('click').on('click', function (ev) {

                ev.preventDefault();
                var $link = $(this).attr('href');
                var url = document.createElement('a');

                url.href = $link;

                window.console.log("requesting cancellation for");
            });
            */
        },
        set_update: function( $element ) {
            var self = this;

            var element = $($element);
            var $settings = element.closest('div.ml-row-settings');
            var users_id = $settings.find('input.e20r-members-list-user-id').val();
            var field_name = $settings.find('input.e20r-members-list-field-name').val();
            var $label = $settings.prev('a.e20r-members-list-' + field_name + '-label');
            var $new_value_field = $settings.find('input.e20r-members-list-db-' + field_name);
            var select = $settings.find('.e20r-members-list-select-' + field_name);

            var $checkbox = element.closest('tr').find('th.check-column input[type="checkbox"]');

            var $date = $settings.find('.e20r-members-list-input-' + field_name).val();
            var select_val = select.val();
            var test_checked = false;

            window.console.log("Value: ", $date, select_val);

            if ('undefined' !== select_val) {

                $label.text(select.find('option:selected').text());
                test_checked = true;
            }

            if (null !== $date) {

                var date = new Date($date);

                window.console.log("Date info? ", date);

                if (Object.prototype.toString.call(date) === "[object Date]") {
                    $label.text(date.toLocaleDateString(e20rml.locale, self.dateOpts));
                }

                test_checked = true;
            }

            if (true === test_checked && false === $checkbox.is('checked')) {
                window.console.log("Checkbox not checked. Fixing!");
                $checkbox.prop('checked', true);

                self.bulkActionSelectBottom.val('bulk-update');
                self.bulkActionSelectTop.val('bulk-update');
                $('#doaction').val(e20rml.lang.save_btn_text);
                $('#doaction2').val(e20rml.lang.save_btn_text);
            }
            /*
            $settings.toggle();
            $label.toggle();
            */
        },
        /**
         * Export data to CSV file (from web server)
         *
         * @credit https://gist.github.com/DavidMah/3533415
         *
         * @param source
         * @param data
         */
        submit_export: function (source, data) {

            window.console.log("About to transmit: ", data);

            var form = $('<form></form>').attr('action', e20rml.url).attr('method', 'post');

            Object.keys(data).forEach(function (key) {
                var value = data[key];

                if (value instanceof Array) {
                    value.forEach(function (v) {
                        form.append($('<input></input>').attr('type', 'hidden').attr('name', key + "[]").attr('value', v));
                    });
                } else {
                    form.append($('<input></input>').attr('type', 'hidden').attr('name', key).attr('value', value));
                }
            });

            // Send the request.
            form.appendTo('body').submit().remove();
        },
        is_empty: function (data) {

            if (typeof(data) === 'number' || typeof(data) === 'boolean') {
                return false;
            }

            if (typeof(data) === 'undefined' || data === null) {
                return true;
            }

            if (typeof(data.length) !== 'undefined') {
                return data.length === 0;
            }

            var count = 0;

            for (var i in data) {

                if (data.hasOwnProperty(i)) {
                    count++;
                }
            }

            return count === 0;
        }
    };

    $(document).ready(function () {
        e20rMembersList_Page.init();
    });

})(jQuery);
