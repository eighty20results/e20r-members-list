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
    let e20rMembersList_Page = {
        init: function () {

			// this.levels_dropdown = $('select#e20r-pmpro-memberslist-levels');
			// this.enddate_lnk = $('a.e20r-members-list_enddate');
			// this.cancelMemberLnk = $('a.e20r-cancel-member');
			// this.updateBtn = $('a.e20r-members-list-save');
			this.memberslist_form = $('.e20r-pmpro-memberslist-page form#posts-filter');
			this.edit_lnk = $('a.e20r-members-list-editable');
			this.resetBtn = $('a.e20r-members-list-cancel');
            this.updateMemberLnk = $('a.e20r-update-member');
            this.exportBtn = $('a.e20r-memberslist-export');
            this.changed_input = $('input[class^="e20r-members-list-input-"]');
            this.changed_select = $( 'select[class^="e20r-members-list-select-"]');
			this.levels_dropdown = $('#e20r-pmpro-memberslist-levels');
			this.bulkUpdate = $('#doaction, #doaction2');
			this.updateListBtn = $('#e20r-update-list');
			this.search_field = $('#post-search-input');
			this.dateFields = $('.e20r-members-list-input-enddate, .e20r-members-list-input-startdate');
			this.dataSearchBtn = $('#e20r-memberslist-search-data');
            this.bulkActionSelectTop = $('select#bulk-action-selector-top');
            this.bulkActionSelectBottom = $('select#bulk-action-selector-bottom');

            this.dateOpts = {
                year: '2-digit', month: 'short',
                day: 'numeric'
            };

            let self = this;

            self.dateFields.datepicker({
                dateFormat: "yy-mm-dd"
            });

            self.search_field.unbind('keypress').on('keypress', function(event){
            	let keycode = (event.key ? event.key : event.keyCode);
            	if ('Enter' !== keycode) {
					return;
				}

            	self.dataSearchBtn.click();
			});

            // self.changed_input.unbind('blur').on('blur', function(ev) {
            self.changed_input.unbind('blur').on('blur', function() {
                self.set_update( this );
            });

			// self.updateListBtn.unbind('click').on('click', function(ev) {
            self.updateListBtn.unbind('click').on('click', function() {

				if ( 'Clear Search' === self.updateListBtn.val() ) {
                	console.log("We're clearing the search...");
                    window.console.log(e20rml.url);
                    window.location.assign(e20rml.url);
                }

                $('#post-search-input').val(null);
            });

            // Trigger search whenever the levels drop-down is changed
            self.levels_dropdown.unbind('change').on('change', function() {
				self.dataSearchBtn.click();
			});

			/**
			 * Search operation initiated (a few other things simulate clicking the button)
			 */

			self.dataSearchBtn.unbind('click').on('click', function(event) {

				let $search_string = $('#post-search-input').val();
				let $uri = window.location.toString();

				// If we have a string in the search box we'll append it to the URL
				if ($search_string) {
					event.preventDefault();

					// URL Encode the search string and add or replace it for the URI
					$uri = self.set_search( $uri, 'find', encodeURIComponent( $search_string ) )
					console.log( 'New URI is: ' + $uri);

					// Now we trigger the search
					window.location = $uri;

					// Clear the search field - Possible FIXME if user's do not want clearing the search field to happen
					$('#post-search-input').val(null);
				}
			});

            self.changed_select.unbind('change').on('change', function() {

                let current_select = $(this);
                let current_select_info = current_select.val();
                let $field_name = current_select.closest('div.ml-row-settings').find('.e20r-members-list-field-name').val();
                let previous_select_info = current_select.closest('div.ml-row-settings').find('input.e20r-members-list-db_' + $field_name ).val();
                let enddate = $('#the-list').find('.last .e20r-members-list-db-enddate').val();

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

                ev.preventDefault();

                if ('bulk-export' === self.bulkActionSelectTop.val() || 'bulk-export' === self.bulkActionSelectBottom.val()) {
                    let inputs = $('.e20r-search-arguments input, .e20r-search-arguments textarea, .e20r-search-arguments select')
                        .not(':input[type=button], :input[type=submit], input[type=reset]');
                    let export_args = {};

                    self.prepare_export(self, export_args, inputs);
                    return true;
                }

                self.memberslist_form.submit();
            });

            self.updateMemberLnk.unbind('click').on('click', function (ev) {

                ev.preventDefault();
                let btn = $(this);
                let row = btn.closest('tr');

                let membership_col = row.find('td.column-membership');
                let membership_settings = membership_col.find('div.ml-row-settings');
                let membership_label = membership_col.find('a.e20r-members-list-editable');

                let startdate_col = row.find('td.column-startdate');
                let startdate_settings = startdate_col.find('div.ml-row-settings');
                let startdate_label = startdate_col.find('a.e20r-members-list-editable');

                let enddate_col = row.find('td.column-last');
                let enddate_settings = enddate_col.find('div.ml-row-settings');
                let enddate_label = enddate_col.find('a.e20r-members-list-editable');

                let status_col = row.find( 'td.column-status');
                let status_settings = status_col.find('div.ml-row-settings');
                let status_label = status_col.find('a.e20r-members-list-editable');

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

                let $edit = $(this);
                let $input = $edit.next('div.ml-row-settings');

                $input.toggle();
                $edit.toggle();

            });

            /**
             * Cancel membership link handler
             */
            self.resetBtn.unbind('click').on('click', function (ev) {

                ev.preventDefault();

                let $btn = $(this);
                let $settings = $btn.closest('div.ml-row-settings');
                let $label = $settings.prev('a.e20r-members-list-editable');
                let $field_name = $settings.find('.e20r-members-list-field-name').val();

                // Return the select value (or input value) to its original value...
                let $original = $settings.find('.e20r-members-list-db-' + $field_name ).val();
                let $field_input_key = 'e20r-members-list-new_' + $field_name;

                $settings.find('select[name^="' + $field_input_key + '"], input[name^="'+ $field_input_key + '"]').val( $original );

                $settings.toggle();
                $label.toggle();
            });

            self.exportBtn.unbind('click').on('click', function (ev) {

                ev.preventDefault();
                window.console.log("Export button clicked!");
                let export_args = {};
                let inputs = $('.e20r-search-arguments input, .e20r-search-arguments textarea, .e20r-search-arguments select')
                    .not(':input[type=button], :input[type=submit], input[type=reset]');

                self.prepare_export(self, export_args, inputs);
            });
        },
		set_search: function(url, paramName, paramValue) {

			let pattern = new RegExp('\\b('+paramName+'=).*?(&|#|$)');

			if (paramValue == null) {
				paramValue = '';
			}

			if (url.search(pattern)>=0) {
				return url.replace(pattern,'$1' + paramValue + '$2');
			}

			url = url.replace(/[?#]$/,'');
			return url + (url.indexOf('?')>0 ? '&' : '?') + paramName + '=' + paramValue;
		},
		prepare_export: function( self, export_args, inputs ) {

			export_args.action = "e20rml_export_records";
			export_args._wpnonce = $('#_wpnonce').val();
			export_args.showDebugTrace = true;

			inputs.each(function () {

				let input = $(this);
				let name = input.attr('name');
				let value = input.val();

				if (false === self.is_empty(value)) {
					window.console.log(name + " contains " + value);

					export_args[name] = value;
				}
			});

			let is_checked = false;
			let selected_ids = [];

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
		},
        set_update: function( $element ) {
            let self = this;

            let element = $($element);
            let $settings = element.closest('div.ml-row-settings');
            // let users_id = $settings.find('input.e20r-members-list-user-id').val();
            let field_name = $settings.find('input.e20r-members-list-field-name').val();
            let $label = $settings.prev('a.e20r-members-list-' + field_name + '-label');
            // let $new_value_field = $settings.find('input.e20r-members-list-db-' + field_name);
            let select = $settings.find('.e20r-members-list-select-' + field_name);

            let $checkbox = element.closest('tr').find('th.check-column input[type="checkbox"]');

            let $date = $settings.find('.e20r-members-list-input-' + field_name).val();
            let select_val = select.val();
            let test_checked = false;

            window.console.log("Value: ", $date, select_val);

            if ('undefined' !== select_val) {

                $label.text(select.find('option:selected').text());
                test_checked = true;
            }

            if (null !== $date) {

                let date = new Date($date);

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

            let form = $('<form></form>').attr('action', e20rml.url).attr('method', 'post');

            Object.keys(data).forEach(function (key) {
                let value = data[key];

                if (value instanceof Array) {
                    value.forEach(function (v) {
                        form.append($('<input />').attr('type', 'hidden').attr('name', key + "[]").attr('value', v));
                    });
                } else {
                    form.append($('<input />').attr('type', 'hidden').attr('name', key).attr('value', value));
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

            let count = 0;

            for (let i in data) {

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
