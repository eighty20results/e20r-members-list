<?php
/**
 *
 * Copyright (c) 2016 - 2022 - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package E20R\Tests\Fixtures
 */

namespace E20R\Tests\Fixtures;

use mysqli_result;

/**
 * Do the tables we need for our testing exist?
 *
 * @return false|void
 */
function fixture_test_tables_exist() {
	global $wpdb;

	if ( null === $wpdb ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
		trigger_error( 'WordPress environment is not running. Invalid test' );
	}

	$table_names = array(
		$wpdb->pmpro_memberships_users,
		$wpdb->pmpro_membership_orders,
		$wpdb->pmpro_membership_levels,
		$wpdb->usermeta,
		$wpdb->users,
	);

	foreach ( $table_names as $table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		if ( empty( $result ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( "Error: Table {$table} does not exist!" );
			return false;
		}
	}

	return true;
}

/**
 * Insert test user records in database
 *
 * @return bool|int|mysqli_result|resource|null
 */
function fixture_insert_user_records() {
	global $wpdb;

	if ( null === $wpdb ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
		trigger_error( 'WordPress environment is not running. Invalid test' );
	}

	$sql  = "REPLACE INTO {$wpdb->users} VALUES ";
	$sql .= "(2,'test_user_1','\$P\$B7qBbJuM9M38/cwuY3S5gIs.YM7IYw/','test_user_1','test_user_1@example.com','','2022-02-12 09:36:33','',0,'Test User1'),";
	$sql .= "(3,'test_user_2','\$P\$B.hqQoTosqb3O.AUwRiIu5qU6y/xnJ1','test_user_2','test_user_2@example.com','','2022-02-12 09:38:21','',0,'Test User2');";

	// Insert user data
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	return $wpdb->query( $sql );
}

/**
 * Insert usermeta for test purposes
 *
 * @return bool|int|mysqli_result|resource|null
 */
function fixture_insert_usermeta() {
	global $wpdb;

	if ( null === $wpdb ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
		trigger_error( 'WordPress environment is not running. Invalid test' );
	}

	$sql  = "REPLACE INTO {$wpdb->usermeta} VALUES ";
	$sql .= "(19,2,'nickname','test_user_1'),(20,2,'first_name','Test'),";
	$sql .= "(21,2,'last_name','User1'),(22,2,'description','')";
	$sql .= ",(23,2,'rich_editing','true'),(24,2,'syntax_highlighting','true')";
	$sql .= ",(25,2,'comment_shortcuts','false'),";
	$sql .= "(26,2,'admin_color','fresh'),(27,2,'use_ssl','0'),(28,2,'show_admin_bar_front','true'),(29,2,'locale',''),";
	$sql .= "(30,2,'wp_capabilities','a:1:{s:10:\"subscriber\";b:1;}'),(31,2,'wp_user_level','0'),";
	$sql .= "(32,2,'session_tokens','a:2:{s:64:\"0be1b6f7347cdde32e4cb47daa5199f474125d754d51f981e67b05497381e1ce\";a:4:{s:10:\"expiration\";i:16
45868193;s:2:\"ip\";s:10:\"172.25.0.1\";s:2:\"ua\";s:120:\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.80 Safari/537.36\";s:5:\"login\";i:1644658593;}s:64:\"db1e71412d6400ac1432449b60e74fe68824a6126bce5d1842245ea118c67635\";a:4:{s:10:\"expiration\";i:1645868193;s:2:\"ip\";s:10:\"172.25.0.1\";s:2:\"ua\";s:120:\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.80 Safari/537.36\";s:5:\"login\";i:1644658593;}}'),";
	$sql .= "(33,2,'pmpro_logins','a:9:{s:4:\"last\";s:17:\"February 12, 2022\";s:8:\"thisdate\";N;s:4:\"week\";i:1;s:8:\"thisweek\";s:2:\"06\";s:5:\"month\";i:1;s:9:\"thismonth\";s:1:\"2\";s:3:\"ytd\";i:1;s:8:\"thisyear\";s:4:\"2022\";s:7:\"alltime\";i:1;}'),";
	$sql .= "(34,2,'pmpro_CardType','Visa'),(35,2,'pmpro_AccountNumber','XXXX-XXXX-XXXX-4242'),(36,2,'pmpro_ExpirationMonth','10'),";
	$sql .= "(37,2,'pmpro_ExpirationYear','2025'),(38,2,'pmpro_bfirstname','Test'),(39,2,'pmpro_blastname','User1'),";
	$sql .= "(40,2,'pmpro_baddress1','123 Nostreet'),(41,2,'pmpro_baddress2',''),(42,2,'pmpro_bcity','Oslo'),";
	$sql .= "(43,2,'pmpro_bstate','Oslo'),(44,2,'pmpro_bzipcode','0571'),(45,2,'pmpro_bcountry','NO'),(46,2,'pmpro_bphone','1234567890'),";
	$sql .= "(47,2,'pmpro_bemail','test_user_1@example.com'),";
	$sql .= "(48,2,'pmpro_views','a:9:{s:4:\"last\";s:17:\"February 12, 2022\";s:8:\"thisdate\";N;s:4:\"week\";i:1;s:8:\"thisweek\";s:2:\"06\";s:5:\"month\";i:1;s:9:\"thismonth\";s:1:\"2\";s:3:\"ytd\";i:1;s:8:\"thisyear\";s:4:\"2022\";s:7:\"alltime\";i:1;}'),";
	$sql .= "(49,3,'nickname','test_user_2'),(50,3,'first_name','Test'),(51,3,'last_name','User2'),(52,3,'description',''),";
	$sql .= "(53,3,'rich_editing','true'),(54,3,'syntax_highlighting','true'),(55,3,'comment_shortcuts','false'),";
	$sql .= "(56,3,'admin_color','fresh'),(57,3,'use_ssl','0'),(58,3,'show_admin_bar_front','true'),(59,3,'locale',''),";
	$sql .= "(60,3,'wp_capabilities','a:1:{s:10:\"subscriber\";b:1;}'),(61,3,'wp_user_level','0'),";
	$sql .= "(62,3,'session_tokens','a:2:{s:64:\"1b9d09d1e9c87aa6dcc846c8a29b2279044120ace34692d41af86a91b4d0ab02\";a:4:{s:10:\"expiration\";i:1645868302;s:2:\"ip\";s:10:\"172.25.0.1\";s:2:\"ua\";s:120:\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.80 Safari/537.36\";s:5:\"login\";i:1644658702;}s:64:\"27de0b4225084355d89f58c576c91ebebb85efe5a2f37870cb83f6439517f523\";a:4:{s:10:\"expiration\";i:1645868302;s:2:\"ip\";s:10:\"172.25.0.1\";s:2:\"ua\";s:120:\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.80 Safari/537.36\";s:5:\"login\";i:1644658702;}}'),";
	$sql .= "(63,3,'pmpro_logins','a:9:{s:4:\"last\";s:17:\"February 12, 2022\";s:8:\"thisdate\";N;s:4:\"week\";i:1;s:8:\"thisweek\";s:2:\"06\";s:5:\"month\";i:1;s:9:\"thismonth\";s:1:\"2\";s:3:\"ytd\";i:1;s:8:\"thisyear\";s:4:\"2022\";s:7:\"alltime\";i:1;}'),(64,3,'pmpro_CardType','Visa'),";
	$sql .= "(65,3,'pmpro_AccountNumber','XXXX-XXXX-XXXX-4242'),(66,3,'pmpro_ExpirationMonth','01'),(67,3,'pmpro_ExpirationYear','2026'),(68,3,'pmpro_bfirstname','Test'),";
	$sql .= "(69,3,'pmpro_blastname','User2'),(70,3,'pmpro_baddress1','234 Nostreet'),(71,3,'pmpro_baddress2',''),(72,3,'pmpro_bcity','Oslo'),(73,3,'pmpro_bstate','Oslo'),";
	$sql .= "(74,3,'pmpro_bzipcode','0517'),(75,3,'pmpro_bcountry','NO'),(76,3,'pmpro_bphone','1234567890'),(77,3,'pmpro_bemail','test_user_2@example.com'),";
	$sql .= "(78,3,'pmpro_views','a:9:{s:4:\"last\";s:17:\"February 12, 2022\";s:8:\"thisdate\";N;s:4:\"week\";i:1;s:8:\"thisweek\";s:2:\"06\";s:5:\"month\";i:1;s:9:\"thismonth\";s:1:\"2\";s:3:\"ytd\";i:1;s:8:\"thisyear\";s:4:\"2022\";s:7:\"alltime\";i:1;}');";

	// Insert user metadata
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	return $wpdb->query( $sql );
}

/**
 * Insert the PMPro order(s) we need for testing
 *
 * @return bool|int|mysqli_result|resource|null
 */
function fixture_insert_order_data() {
	global $wpdb;

	if ( null === $wpdb ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
		trigger_error( 'WordPress environment is not running. Invalid test' );
	}

	$sql  = "REPLACE INTO {$wpdb->pmpro_membership_orders} VALUES ";
	$sql .= "(1,'4240DB7E44','c87a9d2f9028edc0cdd7b0f9fda961e4',2,1,'','Test User1','123 Nostreet','Oslo','Oslo','0571','NO','1234567890','25','0','',1,0,'','25','','Visa','XXXXXXXXXXXX4242','10','2025','success','','sandbox','TEST4240DB7E44','','2022-02-12 09:36:34','','',''),";
	$sql .= "(2,'E26C893AC4','e58966f4c242d4835fd72d55f60174bd',3,2,'','Test User2','234 Nostreet','Oslo','Oslo','0517','NO','1234567890','15','0','',2,0,'','15','','Visa','XXXXXXXXXXXX4242','01','2026','success','','sandbox','TESTE26C893AC4','TESTE26C893AC4','2022-02-12 09:38:22','','','');";

	// Insert user metadata
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	return $wpdb->query( $sql );
}

/**
 * Insert the PMPro membership level info we need for testing
 *
 * @return bool|int|mysqli_result|resource|null
 */
function fixture_insert_level_data() {
	global $wpdb;

	if ( null === $wpdb ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
		trigger_error( 'WordPress environment is not running. Invalid test' );
	}

	$sql  = "REPLACE INTO {$wpdb->pmpro_membership_levels} VALUES ";
	$sql .= "(1,'Test Level 1 (1 year long)','','',25.00000000,0.00000000,0,'',0,0.00000000,0,1,1,'Year'),";
	$sql .= "(2,'Test Level 2 (Recurring)','','',15.00000000,10.00000000,1,'Month',0,0.00000000,0,1,0,'');";

	// Insert user metadata
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	return $wpdb->query( $sql );
}

/**
 * Insert the PMPro Member (user) data we need for testing
 *
 * @return bool|int|mysqli_result|resource|null
 */
function fixture_insert_member_data() {
	global $wpdb;

	if ( null === $wpdb ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
		trigger_error( 'WordPress environment is not running. Invalid test' );
	}

	$sql  = "REPLACE INTO {$wpdb->pmpro_memberships_users} VALUES ";
	$sql .= "(1,2,1,0,25.00000000,0.00000000,0,'',0,0.00000000,0,'active','2022-02-12 09:36:33','2023-02-12 23:59:59','2022-02-12 09:36:34'),";
	$sql .= "(2,3,2,0,15.00000000,10.00000000,1,'Month',0,0.00000000,0,'active','2022-02-12 09:38:22','0000-00-00 00:00:00','2022-02-12 09:38:22');";

	// Insert user metadata
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	return $wpdb->query( $sql );
}
