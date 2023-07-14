<?php

/**
 * Test the wp-graphql settings page for saved queries.
 */

class AdminSettingsQueriesCest
{
	public function _after( FunctionalTester $I ) {
		$I->dontHaveOptionInDatabase( 'graphql_persisted_queries_section'  );
		$I->dontHaveOptionInDatabase( 'graphql_cache_section'  );
	}

	public function saveAllowOnlySettingsTest( FunctionalTester $I ) {
		$I->loginAsAdmin();

		$I->amOnPage('/wp-admin/admin.php?page=graphql-settings#graphql_persisted_queries_section');
		$I->selectOption("form input[type=radio]", 'only_allowed');

		// Save and see the selection after form submit
		$I->click('Save Changes');
		$I->seeOptionIsSelected('form input[type=radio]', 'only_allowed');
	}

	public function testChangeAllowTriggersPurge( FunctionalTester $I ) {
		$I->wantTo( 'Change the allow/deny grant global setting and verify cache is purged' );

		// Enable caching for this test
		$I->haveOptionInDatabase( 'graphql_cache_section', [ 'cache_toggle' => 'on' ] );

		// put something in transient cache
		$transient_name = '_transient_gql_cache_foo:bar';
		$I->haveOptionInDatabase( $transient_name, [ 'bizz' => 'bang' ] );

		// verify it's there
		$transients = unserialize( $I->grabFromDatabase( 'wp_options', 'option_value', [ 'option_name' => $transient_name ] ) );
		$I->assertEquals( 'bang', $transients['bizz'] );

		// change the allow/deny setting in admin
		$I->loginAsAdmin();
		$I->amOnPage('/wp-admin/admin.php?page=graphql-settings#graphql_persisted_queries_section');
		$I->selectOption("form input[type=radio]", 'only_allowed');
		$I->click('Save Changes');

		// verify the transient is gone
		$transients = unserialize( $I->grabFromDatabase( 'wp_options', 'option_value', [ 'option_name like' => '_transient_gql_cache_%' ] ) );
		$I->assertEmpty( $transients );
	}

	public function saveSettingCleanUpEnableTest( FunctionalTester $I ) {
		$I->loginAsAdmin();

		$I->amOnPage('/wp-admin/admin.php?page=graphql-settings#graphql_persisted_queries_section');
		$I->checkOption("//input[@type='checkbox' and @name='graphql_persisted_queries_section[query_gc]']");
		$I->click('Save Changes');
		$I->seeCheckboxIsChecked("//input[@type='checkbox' and @name='graphql_persisted_queries_section[query_gc]']");

		// Verify the cron event has been scheduled
		$cron_names = [];
		// The next few lines extracts the weird WP event schedule shape from the database.
		$cron = unserialize( $I->grabFromDatabase( 'wp_options', 'option_value', [ 'option_name' => 'cron' ] ) );
		foreach ( $cron as $events ) {
			if ( is_array( $events ) ) {
				$cron_names = array_merge( $cron_names, array_keys( $events ) );
			}
		}
		codecept_debug( $cron_names );
		$I->assertContains( 'wp_graphql_smart_cache_cron_query_cleanup', $cron_names );

		$I->uncheckOption("//input[@type='checkbox' and @name='graphql_persisted_queries_section[query_gc]']");
		$I->click('Save Changes');
		$I->dontSeeCheckboxIsChecked("//input[@type='checkbox' and @name='graphql_persisted_queries_section[query_gc]']");

		// Verify the cron event has been removed
		$cron_names = [];
		// The next few lines extracts the weird WP event schedule shape from the database.
		$cron = unserialize( $I->grabFromDatabase( 'wp_options', 'option_value', [ 'option_name' => 'cron' ] ) );
		foreach ( $cron as $events ) {
			if ( is_array( $events ) ) {
				$cron_names = array_merge( $cron_names, array_keys( $events ) );
			}
		}
		codecept_debug( $cron_names );
		$I->assertNotContains( 'wp_graphql_smart_cache_cron_query_cleanup', $cron_names );
	}

	// Test the garbage collection number of days validate and saves
	public function saveSettingCleanUpDaysTest( FunctionalTester $I ) {
		$I->loginAsAdmin();

		$I->amOnPage('/wp-admin/admin.php?page=graphql-settings#graphql_persisted_queries_section');

		$I->seeInField(['name' => 'graphql_persisted_queries_section[query_gc_age]'], '30');
		$I->fillField(['name' => 'graphql_persisted_queries_section[query_gc_age]'], '50');
		$I->click('Save Changes');
		$I->seeInField(['name' => 'graphql_persisted_queries_section[query_gc_age]'], '50');

		// If invalid value, should return previous saved value
		$I->fillField(['name' => 'graphql_persisted_queries_section[query_gc_age]'], '-1');
		$I->click('Save Changes');
		$I->seeInField(['name' => 'graphql_persisted_queries_section[query_gc_age]'], '50');

	}

}
