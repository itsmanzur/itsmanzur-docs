import { FormTokenField, Spinner } from '@wordpress/components';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useProductLinks, useProductSearch } from './hooks';
import type { ProductOption } from './hooks';

interface Props {
	articleId: number;
}

const wrapperStyle: React.CSSProperties = {
	marginTop: '4px',
};

const hintStyle: React.CSSProperties = {
	display: 'block',
	marginTop: '4px',
	fontSize: '11px',
	color: '#64748b',
};

/**
 * Multi-select over WooCommerce products: tokens are product names rather
 * than IDs, resolved back to IDs on change via a name → product lookup
 * built from whatever is currently linked plus the latest search results.
 * Two products sharing an exact name would collide in that lookup — a
 * real per-token ID would need a heavier custom control than
 * FormTokenField offers.
 * @param root0
 * @param root0.articleId
 */
export function ProductLinkPicker( { articleId }: Props ) {
	const { links, isLoading, save } = useProductLinks( articleId );
	const [ query, setQuery ] = useState( '' );
	const { results, isSearching } = useProductSearch( query );
	const [ isSaving, setIsSaving ] = useState( false );

	const knownByName = useMemo( () => {
		const map = new Map< string, ProductOption >();
		[ ...links, ...results ].forEach( ( product ) => {
			map.set( product.name, product );
		} );
		return map;
	}, [ links, results ] );

	const handleChange = ( tokens: ( string | { value: string } )[] ) => {
		const names = tokens.map( ( token ) =>
			typeof token === 'string' ? token : token.value
		);
		const resolved = names
			.map( ( name ) => knownByName.get( name ) )
			.filter( ( product ): product is ProductOption =>
				Boolean( product )
			);

		setIsSaving( true );
		void save( resolved ).finally( () => setIsSaving( false ) );
	};

	if ( isLoading ) {
		return (
			<div style={ wrapperStyle }>
				<Spinner />
			</div>
		);
	}

	return (
		<div style={ wrapperStyle }>
			<FormTokenField
				label={ __( 'Linked products', 'itsmanzur-docs' ) }
				value={ links.map( ( product ) => product.name ) }
				suggestions={ results.map( ( product ) => product.name ) }
				onInputChange={ setQuery }
				onChange={ handleChange }
				placeholder={ __(
					'Search WooCommerce products…',
					'itsmanzur-docs'
				) }
			/>
			{ isSearching && (
				<span style={ hintStyle }>
					{ __( 'Searching…', 'itsmanzur-docs' ) }
				</span>
			) }
			{ isSaving && (
				<span style={ hintStyle }>
					{ __( 'Saving…', 'itsmanzur-docs' ) }
				</span>
			) }
		</div>
	);
}
