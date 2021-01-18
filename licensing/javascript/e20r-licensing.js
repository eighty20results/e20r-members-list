/*
 * *
 *   * Copyright (c) 2020. - Eighty / 20 Results by Wicked Strong Chicks.
 *   * ALL RIGHTS RESERVED
 *   *
 *   * This program is free software: you can redistribute it and/or modify
 *   * it under the terms of the GNU General Public License as published by
 *   * the Free Software Foundation, either version 3 of the License, or
 *   * (at your option) any later version.
 *   *
 *   * This program is distributed in the hope that it will be useful,
 *   * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   * GNU General Public License for more details.
 *   *
 *   * You should have received a copy of the GNU General Public License
 *   * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
(function ($) {
	"use strict";

	var e20r_licensing = {
		init: function () {
			this.verify_btn = $('.e20r-check-license');

			this.verify_btn.on('click', function (event) {
				var btn = $(this);
				var license_info = btn.closest('.e20r-license-data-row');

				var license_key = license_info.find('.e20r-license-key-column')
					.find("input[type='password'][name^='e20r_license_settings[license_key]']")
					.val();

				var email_address = license_info.find('.e20r-license-email-column')
					.find("input[type='email'][name^='e20r_license_settings[license_email]']")
					.val();

				var product_sku = license_info.find('.e20r-license-key-column')
					.find("input[name^='e20r_license_settings[product_sku]']")
					.val();

				var product_name = license_info.find('.e20r-license-key-column')
					.find("input[name^='e20r_license_settings[fulltext_name]']")
					.val();

				// $('body').css('cursor', 'wait');

				var payload = {
					'action': 'e20r_license_verify',
					'license_key': license_key,
					'license_email': email_address,
					'product_sku': product_sku,
					'product_name': product_name,
					'_wpnonce': $('input#_wpnonce').val()
				};

				// $.ajax({});
				window.console.log('Payload to transmit: ', payload);
				event.preventDefault();
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					timeout: 30000,
					dataType: 'JSON',
					data: payload,
					success: function ($response) {

						if ($response.success === false && $response.data.length > 0) {
							window.alert($response.data);
							return;
						}

						location.reload(true);
					},
					error: function (hdr, $error, errorThrown) {
						$('body').css( 'cursor', 'default' );
						window.alert("Error ( " + $error + " ) while verifying license");
						window.console.log("Error:", errorThrown, $error, hdr);
					}
				});

			});
		}
	};

	$(document).ready(function () {
		e20r_licensing.init();
	});
})(jQuery);
