<?php

namespace WordPressdotorg\Pattern_Directory\Search;
use WP_Query;

/*
 * Enable Jetpack Search without a formal plan.
 *
 * The Pattern Directory has a custom index on WPCOM, similar to the the Plugin Directory.
 */
add_filter( 'pre_option_has_jetpack_search_product', '__return_true' );

add_filter( 'option_jetpack_active_modules', __NAMESPACE__ . '\enable_search_module' );
add_filter( 'jetpack_search_should_handle_query', __NAMESPACE__ . '\should_handle_query', 10, 2 );
add_filter( 'jetpack_sync_post_meta_whitelist', __NAMESPACE__ . '\sync_pattern_meta' );

add_action( 'jetpack_search_abort', __NAMESPACE__ . '\log_aborted_queries', 10, 2 );
add_action( 'failed_jetpack_search_query', __NAMESPACE__ . '\log_failed_queries' );


/*
todo

do something to disable instant search?

is there a better way to track console errors than tail-f?
	want to clear screen for each request
	want text formatted to make it easy to read
	text-to-columns so things are aligned
	`lnav` looks ok, maybe the best can do w/out large-scale gui app?


 maybe use these
 jetpack_search_should_handle_query
 do_search() / search()
 "WP Core doesn't call the set_found_posts and its filters when filtering posts_pre_query like we do, so need to do these manually."
 jetpack_search_es_wp_query_args
 store_query_failure / print_query_failure / store_last_query_info
 get_search_result()
 add_post_type_aggregation_to_es_query_builder, also tax, add_es_filters, etc
 info from https://jetpack.com/support/search/?site=wordpress.org::patterns


 * need this stuff from alex's pr?
 * jetpack_active_modules
 * option_jetpack_active_modules
 * jetpack_search_es_wp_query_args
 * jetpack_search_abort
 * did_jetpack_search_query
 *

*/


/**
 * Enable the Search module.
 *
 * This has to be done programmatically since the site doesn't have a Jetpack plan.
 *
 * @param array $modules
 *
 * @return array
 */
function enable_search_module( $modules ) {
	$modules[] = 'search';

	return array_unique( $modules );
}
//add_filter( 'jetpack_active_modules', __NAMESPACE__ . 'enable_jetpack_search_module', 9999 );
// this may be better
//function enable_jetpack_search_module( $modules ) {
//    if ( ! in_array( 'search', $modules, true ) ) {
//        $modules[] = 'search';
//    }
//
//    return $modules;
//}// has to be done before plugins_loaded? see https://docs.wpvip.com/technical-references/elasticsearch/integrating-jetpack-search/

/*
 * worse way?
 * Make sure the search module is available regardless of Jetpack plan.
 * This works because search indexes were manually created for w.org.
 */
//function jetpack_get_module( $module, $slug ) {
//	//var_dump($module, $slug);
//	//			if ( 'search' === $slug && isset( $module[ 'plan_classes' ] ) && !in_array( 'free', $module[ 'plan_classes' ] ) ) {
//	//		if ( 'Search' !== $module['name'] ) {
//	// ^ fragile b/c could be renamed in future?
//	// $slug could be too, but less likely?
//
//	if ( 'search.php' !== basename( $slug ) ) {
//		return $module;
//	}
//
//	$module['plan_classes'][] = 'free';
//
//	//var_dump($module, $slug);
//	return $module;
//}

//	add_filter( 'jetpack_get_module', __NAMESPACE__ . '\jetpack_get_module', 10, 2 );
// this won't be active b/c you're calling it manually?


/**
 * Determine if the search query should be handled by Jetpack/ElasticSearch
 *
 * @param bool $handle_query
 * @param WP_Query $query
 *
 * @return bool
 */
function should_handle_query( $handle_query, $query ) {
	//	wp_send_json($query);

	//	var_dump( $query->is_search() && 'wporg-pattern' === $query->get( 'post_type' ) );die();
	// post_type not set on front end b/c of... rewrite rules? rest_base?
	// that might be fine, though ��‍
	// look into later, move on for now

	// probably only do this for pattern searches specifically, maybe on through rest api too
	// maybe have an init() function that only registers all the callbacks if ^ conditions are met

	// todo why is JS search injected for endpoints like https://api.wordpress.org/patterns/1.0/ ?  it shouldn't be

	return $query->is_search() && 'wporg-pattern' === $query->get( 'post_type' );
}

//add_filter( 'jetpack_search_should_handle_query', function( $handle_query, $query ) {
////	if ( $handle_query ) { // todo - add `|| $query doesn't match pattern directory` condition
////		die('wait why?'); // oh, maybe should return false for front end...
////		// well, module shouldn't be enabled on front-end anyway
////		// unless maybe it needs to be for syncing, etc. maybe that is the better approach
////		// either way, document it
////		// doesn't look like anything bad will happen if enabled for all requests, but also doensn't look like that's neccessary for syncing
////			// well, haven't checked sync code. there could be something that says "only sync x if search module enabled", but so far jptools and wpcom api indicate that post data synced. see what greg says
////		return true;
////	}
//
//	if ( WPORG_PROXIED_REQUEST ) {
////		var_dump( $query ); nothing helful here
////		var_dump( $_REQUEST ); // same
//		wp_send_json( array( $query, $_REQUEST, $_SERVER)  );
//		die();
//REST_REQUEST constant is avail
// woocommerce version - not foolproof
//		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
//			return false;
//		}
//
//		// REST API prefix.
//		$rest_prefix = trailingslashit( rest_get_url_prefix() );
//
//		// Check if this is a WC endpoint.
//		$is_woocommerce_endpoint = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix . 'wc/' ) ); // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
//
//		return apply_filters( 'woocommerce_is_rest_api_request', $is_woocommerce_endpoint );
//
//
//	}
//	/*
//	 * Enable for REST API requests.
//	 *
//	 * This isn't really what `wp_is_json_request()` is meant for, but it's the best option until
//	 * https://core.trac.wordpress.org/ticket/42061 is resolved.
//	 *
//	 * @todo Replace with `wp_doing_rest()` (or whatever) once that's available.
//	 */
//	return wp_is_json_request() && 'patterns' === $rest->get_route(); // prob can't do that this early, but is there something else i can check?
//}, 10, 2 );

// todo need to commit api.w.org http_accept change for ^ to work in prod
	// same for below maybe? or no b/c not using wp_is_json_request

//add_filter( 'jetpack_search_should_handle_query', '__return_false' ); // set teh default
//// override the default sometimes
//// document atht doing it this way b/c there isn't a good way to detect once you're in `jetpack_search_should_handle_query` callback
//add_filter( 'rest_pre_dispatch', function( $result, $server, $request ) {
////	wp_send_json( array( $request->get_route(), $request->get_params(), $request->get_body()  ) );
////	die();
//
//	// https://regex101.com/r/OkSMWT/1/
////	if ( preg_match( '~^/wp/v\d+/wporg-pattern/?$~', $request->get_route() ) ) {
////		// is tehre a way to make ^ not break if the endpoint changes?
////		add_filter( 'jetpack_search_should_handle_query', '__return_true' );
////	}
//
//	// could do something simpler, like: if searching, and post type is wporg-pattern, then jp should handle
//	// could do regardless of rest api or front end, b/c want same results really
//	// or at least it doesn't hurt
//
//	return $result; // don't want to change anything, just needed to use a filter b/c there isn't an action that gives access to this data
//}, 10, 3 );

/**
 * Modify the search query parameters, such as controlling the post_type.
 *
 * These arguments are in the format of WP_Query arguments
 *
 * @module search
 *
 * @param array    $es_wp_query_args The current query args, in WP_Query format.
 * @param WP_Query $query            The original WP_Query object.
 *
 * @since  5.0.0
 *
 */
add_filter( 'jetpack_search_es_wp_query_args', function( $wp_query_args, $query ) {
	//	print_r( $wp_query_args );

	// do anything that _can_ be done here, b/c less fragile

	// can set query_fields here instead of below?
	// looks like, but will that result in the best query, or is it just for back-compat, and not very efficient?

	return $wp_query_args;
}, 10, 2 );

/**
 * Modify the underlying ES query that is passed to the search endpoint. The returned args must represent a valid
 * ES query
 *
 * This filter is harder to use if you're unfamiliar with ES, but allows complete control over the query
 *
 * @param array    $es_query_args The raw Elasticsearch query args.
 * @param WP_Query $query         The original WP_Query object.
 *
 */
add_filter( 'jetpack_search_es_query_args', function( $es_query_args, $query ) {
	//		print_r($es_query_args);

	// limit it to just title and meta.description, etc

	//		die(__FUNCTION__);


	/*
	 * todo tweak query
	 *
	 * see https://docs.wpvip.com/technical-references/elasticsearch/integrating-jetpack-search/
	 *
	 *  "query": {
	    "function_score": {
	        "query": {
	            "bool": {
	                "must": [
	                    {
	                        "multi_match": {
	                            "fields": [
	                                   // change these to just the title, description. anything else? maybe category and keywords?
	                            ],
	                            "query": "button",
	                            "operator": "and"
	                        }
	                    }
	                ],
	                "should": [
	                    {
	                        "multi_match": {
	                            "fields": [
	                                // need to understand diff between this and "must", and type:best_fields, type phrase, etc.
	                            ],
	                            "query": "button",
	                            "operator": "and",
	                            "type": "best_fields"
	                        }
	                    },
	                    {
	                        "multi_match": {
	                            "fields": [
	                                // same as above
	                            ],
	                            "query": "button",
	                            "operator": "and",
	                            "type": "phrase"
	                        }
	                    }
	                ]
	            }
	        },
	*/

	return $es_query_args;
}, 10, 2 );

/**
 * Tell Jetpack to sync pattern meta, so it can be indexed by ElasticSearch.
 *
 * @param array $post_meta_safelist
 *
 * @return array
 */
function sync_pattern_meta( $post_meta_safelist ) {
	$post_meta_safelist[] = 'wpop_description';
	$post_meta_safelist[] = 'wpop_viewport_width';

	return $post_meta_safelist;
}

/**
 * Log when Jetpack does not run the query.
 *
 * @param string $reason
 * @param array  $data
 */
function log_aborted_queries( $reason, $data ) {
	$function = WPORG_SANDBOXED ? 'trigger_error' : 'error_log';

	call_user_func( $function, 'jetpack_search_abort - cc @iandunn, @tellyworth, @dd32 - ' . $reason .' - ' . wp_json_encode( $data ), E_USER_ERROR );
		// don't want this to halt execution in prod, but should in sandbox
		// on prod, want it to show up in dotorg-alerts as a high-priority problem. in dev either slack_dm or just /tmp/php-errors. don't need slack if it halts execution

	// test if `no_search_results_array` is sent for valid things, like searching for "thisdoesnotexist" - that's expected to return 0 results
		// it should _not_ fallback to running the query through WP
		// it also should _not_ trigger an error log entry or fatal

	// maybe also ignore search_attempted_non_search_query ?

	// test that works in dev and prod
}

/**
 * Log when Jetpack gets an error running the query.
 *
 * @param array $data
 */
function log_failed_queries( $data ) {
	// this never fires, see https://github.com/Automattic/jetpack/issues/18888

	$function = WPORG_SANDBOXED ? 'trigger_error' : 'error_log';

	call_user_func( $function, 'failed_jetpack_search_query - cc @iandunn, @tellyworth, @dd32 - ' . wp_json_encode( $data ), E_USER_ERROR );
	// make DRY w/ search_abort behavior on sandbox vs prod?

	// todo what should happen when error occurs? fallback to wp_query search? that's probably most graceful, but won't be good results
	// might be better to hard fail to make sure it's noticed/prioritized?
	// maybe error msg in slack should indicate that need to fix soon b/c users getting really bad qsearch results
}
