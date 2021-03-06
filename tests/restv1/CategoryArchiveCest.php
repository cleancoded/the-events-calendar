<?php

use Step\Restv1\RestGuy as Tester;
use Tribe__Events__Main as Main;

class CategoryArchiveCest extends BaseRestCest {

	/**
	 * It should return 404 if no event category is in db
	 *
	 * @test
	 */
	public function should_return_404_if_no_event_category_is_in_db( Tester $I ) {
		$I->sendGET( $this->categories_url, [ 'hide_empty' => 'false' ] );

		$I->seeResponseCodeIs( 404 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should return available event categories
	 *
	 * @test
	 */
	public function should_return_available_event_categories( Tester $I ) {
		$I->haveManyTermsInDatabase( 5, 'Event Category {{n}}', Main::TAXONOMY );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse(), true );

		$I->assertCount( 5, $response['categories'] );
	}

	/**
	 * It should allow specifying the category archive page to get
	 *
	 * @test
	 */
	public function should_allow_specifying_the_category_archive_page_to_get( Tester $I ) {
		$terms    = $I->haveManyTermsInDatabase( 6, 'Event Category {{n}}', Main::TAXONOMY );
		$term_ids = array_column( $terms, 0 );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 3,
			'page'       => 1,
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse(), true );

		$I->assertCount( 3, $response['categories'] );
		$page_1_terms_ids = array_column( $response['categories'], 'id' );
		$I->assertCount( 3, array_intersect( $page_1_terms_ids, $term_ids ) );

		$term_ids = array_diff( $term_ids, $page_1_terms_ids );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 3,
			'page'       => 2,
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse(), true );

		$I->assertCount( 3, $response['categories'] );
		$page_2_terms_ids = array_column( $response['categories'], 'id' );
		$I->assertCount( 3, array_intersect( $page_2_terms_ids, $term_ids ) );
	}

	/**
	 * It should return the page and link data in the response
	 *
	 * @test
	 */
	public function should_return_the_page_and_link_data_in_the_response( Tester $I ) {
		$I->haveManyTermsInDatabase( 9, 'Event Category {{n}}', Main::TAXONOMY );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 3,
			'page'       => 1,
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse() );
		$I->assertEquals( 9, $response->total );
		$I->assertEquals( 3, $response->total_pages );
		$I->seeHttpHeader( 'X-TEC-Total', 9 );
		$I->seeHttpHeader( 'X-TEC-TotalPages', 3 );
		$I->assertArrayNotHasKey( 'previous_rest_url', (array) $response );
		$I->see_response_contains_url( 'next_rest_url', $this->categories_url . '/?page=2' );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 3,
			'page'       => 2,
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse() );
		$I->assertEquals( 9, $response->total );
		$I->assertEquals( 3, $response->total_pages );
		$I->seeHttpHeader( 'X-TEC-Total', 9 );
		$I->seeHttpHeader( 'X-TEC-TotalPages', 3 );
		$I->see_response_contains_url( 'previous_rest_url', $this->categories_url . '/' );
		$I->see_response_contains_url( 'next_rest_url', $this->categories_url . '/?page=3' );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 3,
			'page'       => 3,
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse() );
		$I->assertEquals( 9, $response->total );
		$I->assertEquals( 3, $response->total_pages );
		$I->seeHttpHeader( 'X-TEC-Total', 9 );
		$I->seeHttpHeader( 'X-TEC-TotalPages', 3 );
		$I->see_response_contains_url( 'previous_rest_url', $this->categories_url . '/?page=2' );
		$I->assertArrayNotHasKey( 'next_rest_url', (array) $response );
	}

	/**
	 * It should allow searching event categories
	 *
	 * @test
	 */
	public function should_allow_searching_event_categories( Tester $I ) {
		$I->haveTermInDatabase( 'foo', Main::TAXONOMY, [ 'name' => 'Foo' ] );
		$I->haveTermInDatabase( 'bar', Main::TAXONOMY, [ 'name' => 'Bar' ] );
		$I->haveTermInDatabase( 'baz', Main::TAXONOMY, [ 'name' => 'Baz' ] );
		$I->haveTermInDatabase( 'foo-bar', Main::TAXONOMY, [ 'name' => 'One' ] );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'search'     => 'foo',
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse() );
		$I->assertEquals( 2, $response->total );
		$I->assertEquals( 1, $response->total_pages );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'search'     => 'bar',
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse() );
		$I->assertEquals( 2, $response->total );
		$I->assertEquals( 1, $response->total_pages );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'search'     => 'zoot',
		] );

		$I->seeResponseCodeIs( 404 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should allow excluding event categories by term ID
	 *
	 * @test
	 */
	public function should_allow_excluding_event_categories_by_term_id( Tester $I ) {
		list( $term_id_one, $taxonomy_term_id_one ) = $I->haveTermInDatabase( 'one', Main::TAXONOMY );
		list( $term_id_two, $taxonomy_term_id_two ) = $I->haveTermInDatabase( 'two', Main::TAXONOMY );
		list( $term_id_three, $taxonomy_term_id_three ) = $I->haveTermInDatabase( 'three', Main::TAXONOMY );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'exclude'    => $term_id_one,
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse() );
		$I->assertEquals( 2, $response->total );
		$I->assertEquals( 1, $response->total_pages );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'exclude'    => [ $term_id_one, $term_id_two ],
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse() );
		$I->assertEquals( 1, $response->total );
		$I->assertEquals( 1, $response->total_pages );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'exclude'    => [ $term_id_one, $term_id_two, $term_id_three ],
		] );

		$I->seeResponseCodeIs( 404 );
		$I->seeResponseIsJson();

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'exclude'    => [ 2389, 1235, 3434 ],
		] );

		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should allow including event categories by term ID
	 *
	 * @test
	 */
	public function should_allow_including_event_categories_by_term_id( Tester $I ) {
		list( $term_id_one, $taxonomy_term_id_one ) = $I->haveTermInDatabase( 'one', Main::TAXONOMY );
		list( $term_id_two, $taxonomy_term_id_two ) = $I->haveTermInDatabase( 'two', Main::TAXONOMY );
		list( $term_id_three, $taxonomy_term_id_three ) = $I->haveTermInDatabase( 'three', Main::TAXONOMY );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'include'    => $term_id_one,
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse() );
		$I->assertEquals( 1, $response->total );
		$I->assertEquals( 1, $response->total_pages );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'include'    => [ $term_id_one, $term_id_two, $term_id_three ],
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse() );
		$I->assertEquals( 3, $response->total );
		$I->assertEquals( 1, $response->total_pages );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'include'    => [ 2389, 1235, 3434 ],
		] );

		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should allow setting ordering the results
	 *
	 * @test
	 */
	public function should_allow_setting_ordering_the_results( Tester $I ) {
		list( $term_id_one, $taxonomy_term_id_one ) = $I->haveTermInDatabase( 'b', Main::TAXONOMY );
		list( $term_id_two, $taxonomy_term_id_two ) = $I->haveTermInDatabase( 'A', Main::TAXONOMY );
		list( $term_id_three, $taxonomy_term_id_three ) = $I->haveTermInDatabase( 'C', Main::TAXONOMY );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'order'      => 'desc',
			'orderby'    => 'id',
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response     = json_decode( $I->grabResponse(), true );
		$response_ids = array_column( $response['categories'], 'id' );
		$I->assertEquals( [ $term_id_three, $term_id_two, $term_id_one ], $response_ids );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'order'      => 'asc',
			'orderby'    => 'id',
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response     = json_decode( $I->grabResponse(), true );
		$response_ids = array_column( $response['categories'], 'id' );
		$I->assertEquals( [ $term_id_one, $term_id_two, $term_id_three ], $response_ids );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'order'      => 'asc',
			'orderby'    => 'name',
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response     = json_decode( $I->grabResponse(), true );
		$response_ids = array_column( $response['categories'], 'id' );
		$I->assertEquals( [ $term_id_two, $term_id_one, $term_id_three ], $response_ids );
	}

	/**
	 * It should return bad request if passing bad parameters for order and orderby parameters
	 *
	 * @test
	 *
	 * @example ["","id"]
	 * @example ["desc",""]
	 */
	public function should_return_bad_request_if_passing_bad_parameters_for_order_and_orderby_parameters( Tester $I, \Codeception\Example $example ) {
		list( $term_id_one, $taxonomy_term_id_one ) = $I->haveTermInDatabase( 'b', Main::TAXONOMY );
		list( $term_id_two, $taxonomy_term_id_two ) = $I->haveTermInDatabase( 'A', Main::TAXONOMY );
		list( $term_id_three, $taxonomy_term_id_three ) = $I->haveTermInDatabase( 'C', Main::TAXONOMY );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'order'      => $example[0],
			'orderby'    => $example[1],
		] );

		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should allow filtering event categories by parent term ID
	 *
	 * @test
	 */
	public function should_allow_filtering_event_categories_by_parent_term_id( Tester $I ) {
		list( $term_id_one, $taxonomy_term_id_one ) = $I->haveTermInDatabase( 'b', Main::TAXONOMY );
		list( $term_id_two, $taxonomy_term_id_two ) = $I->haveTermInDatabase( 'A', Main::TAXONOMY, [ 'parent' => $term_id_one ] );
		list( $term_id_three, $taxonomy_term_id_three ) = $I->haveTermInDatabase( 'C', Main::TAXONOMY );
		list( $term_id_four, $taxonomy_term_id_four ) = $I->haveTermInDatabase( 'D', Main::TAXONOMY, [ 'parent' => $term_id_one ] );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'parent'     => $term_id_one,
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse() );
		$I->assertEquals( 2, $response->total );
		$I->assertEquals( 1, $response->total_pages );
	}

	/**
	 * It should return bad request if passing bad parameter for parent term ID
	 *
	 * @test
	 * @example [2389]
	 * @example [""]
	 */
	public function should_return_bad_request_if_passing_bad_parameter_for_parent_term_id( Tester $I, \Codeception\Example $example ) {
		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'parent'     => $example[0],
		] );

		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should allow filtering event categories by related post ID
	 *
	 * @test
	 */
	public function should_allow_filtering_event_categories_by_related_post_id( Tester $I ) {
		list( $term_id_one, $taxonomy_term_id_one ) = $I->haveTermInDatabase( 'b', Main::TAXONOMY );
		list( $term_id_two, $taxonomy_term_id_two ) = $I->haveTermInDatabase( 'A', Main::TAXONOMY, [ 'parent' => $term_id_one ] );
		list( $term_id_three, $taxonomy_term_id_three ) = $I->haveTermInDatabase( 'C', Main::TAXONOMY );
		list( $term_id_four, $taxonomy_term_id_four ) = $I->haveTermInDatabase( 'D', Main::TAXONOMY, [ 'parent' => $term_id_one ] );
		$event = $I->haveEventInDatabase( [ 'categories' => [ $term_id_one, $term_id_three ] ] );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'post'       => $event,
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse() );
		$I->assertEquals( 2, $response->total );
		$I->assertEquals( 1, $response->total_pages );
	}

	/**
	 * It should alias the post parameter to the event parameter to filter categories by event
	 *
	 * @test
	 */
	public function should_alias_the_post_parameter_to_the_event_parameter_to_filter_categories_by_event( Tester $I ) {
		list( $term_id_one, $taxonomy_term_id_one ) = $I->haveTermInDatabase( 'b', Main::TAXONOMY );
		list( $term_id_two, $taxonomy_term_id_two ) = $I->haveTermInDatabase( 'A', Main::TAXONOMY, [ 'parent' => $term_id_one ] );
		list( $term_id_three, $taxonomy_term_id_three ) = $I->haveTermInDatabase( 'C', Main::TAXONOMY );
		list( $term_id_four, $taxonomy_term_id_four ) = $I->haveTermInDatabase( 'D', Main::TAXONOMY, [ 'parent' => $term_id_one ] );
		$event = $I->haveEventInDatabase( [ 'categories' => [ $term_id_one, $term_id_three ] ] );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'event'      => $event,
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse() );
		$I->assertEquals( 2, $response->total );
		$I->assertEquals( 1, $response->total_pages );
	}

	/**
	 * It should override post parameter when using event parameter
	 *
	 * @test
	 */
	public function should_override_post_parameter_when_using_event_parameter( Tester $I ) {
		list( $term_id_one, $taxonomy_term_id_one ) = $I->haveTermInDatabase( 'b', Main::TAXONOMY );
		list( $term_id_two, $taxonomy_term_id_two ) = $I->haveTermInDatabase( 'A', Main::TAXONOMY, [ 'parent' => $term_id_one ] );
		list( $term_id_three, $taxonomy_term_id_three ) = $I->haveTermInDatabase( 'C', Main::TAXONOMY );
		list( $term_id_four, $taxonomy_term_id_four ) = $I->haveTermInDatabase( 'D', Main::TAXONOMY, [ 'parent' => $term_id_one ] );
		$event_one = $I->haveEventInDatabase( [ 'categories' => [ $term_id_one, $term_id_three ] ] );
		$event_two = $I->haveEventInDatabase( [ 'categories' => [ $term_id_two ] ] );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'post'       => $event_one,
			'event'      => $event_two,
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse() );
		$I->assertEquals( 1, $response->total );
		$I->assertEquals( 1, $response->total_pages );
	}

	/**
	 * It should return bad request if passing bad parameter for post of event post ID
	 *
	 * @test
	 * @example ["post", ""]
	 * @example ["post", 23]
	 * @example ["post", "foo"]
	 * @example ["event", ""]
	 * @example ["event", 23]
	 * @example ["event", "foo"]
	 */
	public function should_return_bad_request_if_passing_bad_parameter_for_post_of_event_post_id( Tester $I, \Codeception\Example $example ) {
		$I->sendGET( $this->categories_url, [
				'hide_empty' => false,
				'per_page'   => 10,
				$example[0]  => $example[1],
			]
		);

		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should allow filtering event categories by slug
	 *
	 * @test
	 */
	public function should_allow_filtering_event_categories_by_slug( Tester $I ) {
		list( $term_id_one, $taxonomy_term_id_one ) = $I->haveTermInDatabase( 'b', Main::TAXONOMY, [ 'slug' => 'b' ] );
		list( $term_id_two, $taxonomy_term_id_two ) = $I->haveTermInDatabase( 'A', Main::TAXONOMY, [ 'slug' => 'a' ] );
		list( $term_id_three, $taxonomy_term_id_three ) = $I->haveTermInDatabase( 'C', Main::TAXONOMY, [ 'slug' => 'c' ] );
		list( $term_id_four, $taxonomy_term_id_four ) = $I->haveTermInDatabase( 'D', Main::TAXONOMY, [ 'slug' => 'd' ] );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'slug'       => 'a',
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse() );
		$I->assertEquals( 1, $response->total );
		$I->assertEquals( 1, $response->total_pages );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'slug'       => 'c',
		] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse() );
		$I->assertEquals( 1, $response->total );
		$I->assertEquals( 1, $response->total_pages );

		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'slug'       => 'foo',
		] );

		$I->seeResponseCodeIs( 404 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should return bad request when providing bad slug parameter
	 *
	 * @test
	 * @example [""]
	 */
	public function should_return_bad_request_when_providing_bad_slug_parameter( Tester $I, \Codeception\Example $example ) {
		$I->sendGET( $this->categories_url, [
			'hide_empty' => false,
			'per_page'   => 10,
			'slug'       => $example[0],
		] );

		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();
	}
}
