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

/* jslint esversion: 6 */
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
                	window.console.log("We're clearing the search...");
                    window.console.log(e20rml.url); //jshint ignore:line
                    window.location.assign(e20rml.url); //jshint ignore:line
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

				let $search_string = $( '#post-search-input' ).val();
				let $uri		   = window.location.toString();

				// If we have a string in the search box we'll append it to the URL
				if ($search_string) {
					event.preventDefault();

					// URL Encode the search string and add or replace it for the URI
					$uri = self.set_search( $uri, 'find', encodeURIComponent( $search_string ) );
					window.console.log( 'New URI should be: ' + $uri );

					// Now we trigger the search
					// window.location = $uri;

					// Clear the search field - Possible FIXME if user's do not want clearing the search field to happen
					// $( '#post-search-input' ).val( null );
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
                    if ( ! window.confirm(e20rml.lang.clearing_enddate) ) { //jshint ignore:line
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
				$("#overlay").fadeIn(300);
				let inputs = $('.e20r-search-arguments input, .e20r-search-arguments textarea, .e20r-search-arguments select')
                    .not(':input[type=button], :input[type=submit], input[type=reset]');

                let export_args = self.prepare_export(self, inputs);
				// Open a new tab to trigger the AJAX request for the download
				self.download_csv('members_list.csv', export_args, ); //jshint ignore:line
            });
        },
		set_search: function(url, paramName, paramValue) {

			let pattern = new RegExp('\\b('+paramName+'=).*?(&|#|$)');

			if (paramValue === null) {
				paramValue = '';
			}

			if (url.search(pattern)>=0) {
				return url.replace(pattern,'$1' + paramValue + '$2');
			}

			url = url.replace(/[?#]$/,'');
			return url + (url.indexOf('?')>0 ? '&' : '?') + paramName + '=' + paramValue;
		},
		/**
		 * Set the request variables based on the members list page
		 *
		 * @param self Instance of this object
		 * @param inputs The HTML inputs
		 * @returns Object
		 */
		prepare_export: function( self, inputs ) {
			let request_args = {
				'_wpnonce':  $('#_wpnonce').val(),
				'showDebugTrace': true,
				'action': 'e20rml_export_records',
			};
			request_args.find = $( '#post-search-input' ).val();
			inputs.each(function () {
				let input = $(this);
				let name = input.attr('name');
				let value = input.val();
				if (false === self.is_empty(value)) {
					window.console.log(name + " contains " + value);
					request_args[name] = value;
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
				request_args.member_id = selected_ids;
			}
			// return self.build_export_url( export_args );
			return request_args;
		},
		/**
		 * Download the exported .CSV data
		 * @param filename The file name to use for download
		 * @param download_args The POST argument(s)
		 */
		download_export: function(filename, download_args) {
			fetch(e20rml.ajax_url, download_args) // jshint ignore:line
				.then(response => {
					if (!response.ok) {
						throw new Error('Request failed.' + response);
					}
				})
				.then(response => response.blob())
				.then(blob => {
					const link = window.document.createElement("a");
					link.href = URL.createObjectURL(blob);
					link.download = filename;
					link.click();
				})
				.catch(window.console.error);
		},
		download_csv: function(file_name, attributes) {
			let URL = e20rml.ajax_url; // jshint ignore:line
			window.console.log( 'AJAX URL: ' + URL);
			window.console.log( attributes );
			$.ajax({
				url: URL,
				cache: false,
				method: 'POST',
				data: attributes,
				accepts: 'text/csv',
				xhr: function () {
					var xhr = new XMLHttpRequest();
					xhr.onreadystatechange = function () {
						if (xhr.readyState === 2) {
							if (xhr.status === 200) {
								xhr.responseType = "blob";
							} else {
								xhr.responseType = "text";
							}
						}
					};
					return xhr;
				},
				success: function (data) {
					//Convert the Byte Data to BLOB object.
					let blob = new Blob([data], { type: "text/csv" });

					//Check the Browser type and download the File.
					let isIE = !!document.documentMode;
					if (isIE) {
						window.navigator.msSaveBlob(blob, file_name);
					} else {
						let url = window.URL || window.webkitURL;
						let link = url.createObjectURL(blob);
						let body = $('body');
						let a = $("<a />");
						a.attr("download", file_name);
						a.attr("href", link);
						body.append(a);
						a[0].click();
						body.remove(a);
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					window.console.log( 'Status: ' + textStatus + ', Error thrown: ' + errorThrown );
				}
			}).done(function() {
				setTimeout(function(){
					$("#overlay").fadeOut(300);
				},500);
			});
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
                    $label.text(date.toLocaleDateString(e20rml.locale, self.dateOpts)); //jshint ignore:line
                }

                test_checked = true;
            }

            if (true === test_checked && false === $checkbox.is('checked')) {
                window.console.log("Checkbox not checked. Fixing!");
                $checkbox.prop('checked', true);

                self.bulkActionSelectBottom.val('bulk-update');
                self.bulkActionSelectTop.val('bulk-update');
                $('#doaction').val(e20rml.lang.save_btn_text); //jshint ignore:line
                $('#doaction2').val(e20rml.lang.save_btn_text); //jshint ignore:line
            }
            /*
            $settings.toggle();
            $label.toggle();
            */
        },
        /**
         * Build the URL used to export the data
         *
         * @param export_attrs The attributes to use for the AJAX URL (export)
         */
		build_export_url: function (export_attrs) {
            window.console.log("About to transmit: ", export_attrs);
			let export_arguments = '';
			Object.keys(export_attrs).forEach(function(key){
				let value = export_attrs[key];
				if ( value instanceof Array) {
					value = value.join(',');
				}
				export_arguments = export_arguments + key + '=' + encodeURI( value ) + '&';
			});

			let location = e20rml.ajax_url + '?' + export_arguments; // jshint ignore:line
			window.console.log( location );
			return location;
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
