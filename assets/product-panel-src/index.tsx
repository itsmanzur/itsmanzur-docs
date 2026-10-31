import { registerPlugin } from '@wordpress/plugins';
import { ProductDocsPanel } from './ProductDocsPanel';

registerPlugin( 'itsdz-product-docs', {
	render: ProductDocsPanel,
} );
