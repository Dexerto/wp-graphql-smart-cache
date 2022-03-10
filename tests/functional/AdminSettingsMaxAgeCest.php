<?php

/**
 * Test the wp-graphql settings page for global max age header.
 */

class AdminSettingsMaxAgeCest
{
	public function _after( FunctionalTester $I ) {
		$I->dontHaveOptionInDatabase( 'graphql_persisted_queries_section'  );
	}

	public function saveMaxAgeSettingsTest( FunctionalTester $I ) {
			$I->loginAsAdmin();

			$I->amOnPage('/wp-admin/admin.php?page=graphql-settings#graphql_persisted_queries_section');
			$I->seeInField(['name' => 'graphql_persisted_queries_section[global_max_age]'], null);
			$I->fillField(['name' => 'graphql_persisted_queries_section[global_max_age]'], '30');

			// Save and see the selection after form submit
			$I->click('Save Changes');
			$I->seeInField(['name' => 'graphql_persisted_queries_section[global_max_age]'], '30');
	}

}
