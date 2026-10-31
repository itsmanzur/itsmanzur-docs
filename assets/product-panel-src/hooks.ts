/**
 * Data fetching for the product-link Gutenberg sidebar panel.
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

export interface ProductOption {
	id: number;
	name: string;
}

/**
 * Debounced WooCommerce product search for the picker's type-ahead —
 * waits for typing to pause before hitting the REST search route, and
 * ignores a response that arrives after the query has since changed.
 * @param query
 */
export function useProductSearch( query: string ): {
	results: ProductOption[];
	isSearching: boolean;
} {
	const [ results, setResults ] = useState< ProductOption[] >( [] );
	const [ isSearching, setIsSearching ] = useState( false );

	useEffect( () => {
		const trimmed = query.trim();

		if ( ! trimmed ) {
			setResults( [] );
			setIsSearching( false );
			return;
		}

		let cancelled = false;
		setIsSearching( true );

		const timeout = window.setTimeout( () => {
			apiFetch< ProductOption[] >( {
				path: `/itsdz/v1/products/search?q=${ encodeURIComponent(
					trimmed
				) }`,
			} )
				.then( ( items ) => {
					if ( ! cancelled ) {
						setResults( items );
						setIsSearching( false );
					}
				} )
				.catch( () => {
					if ( ! cancelled ) {
						setResults( [] );
						setIsSearching( false );
					}
				} );
		}, 300 );

		return () => {
			cancelled = true;
			window.clearTimeout( timeout );
		};
	}, [ query ] );

	return { results, isSearching };
}

/**
 * Load and save the WooCommerce products linked to the current article.
 * @param articleId
 */
export function useProductLinks( articleId: number ): {
	links: ProductOption[];
	isLoading: boolean;
	save: ( products: ProductOption[] ) => Promise< void >;
} {
	const [ links, setLinks ] = useState< ProductOption[] >( [] );
	const [ isLoading, setIsLoading ] = useState( true );

	useEffect( () => {
		if ( ! articleId ) {
			setIsLoading( false );
			return;
		}

		let cancelled = false;

		apiFetch< ProductOption[] >( {
			path: `/itsdz/v1/product-links?article_id=${ articleId }`,
		} )
			.then( ( items ) => {
				if ( ! cancelled ) {
					setLinks( items );
					setIsLoading( false );
				}
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setIsLoading( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ articleId ] );

	const save = async ( products: ProductOption[] ): Promise< void > => {
		setLinks( products );
		await apiFetch( {
			path: '/itsdz/v1/product-links',
			method: 'POST',
			data: {
				article_id: articleId,
				wc_product_ids: products.map( ( product ) => product.id ),
			},
		} );
	};

	return { links, isLoading, save };
}
