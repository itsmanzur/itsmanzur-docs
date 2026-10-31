import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { ProductLinkPicker } from './ProductLinkPicker';

const ARTICLE_POST_TYPE = 'itsdz_doc';

/**
 * Split from `ProductDocsPanel` for the same reason Free's own
 * DoczurPanel/DoczurPanelFields split exists: `registerPlugin()` mounts its
 * `render` component on every post type's edit screen, so the postType
 * check has to happen in an outer component, not via a conditional hook
 * call inside one.
 */
function ProductDocsPanelFields() {
	const postId = useSelect(
		( select ) => select( editorStore ).getCurrentPostId(),
		[]
	);

	return (
		<PluginDocumentSettingPanel
			name="itsdz-product-docs"
			title={ __( 'WooCommerce products', 'itsmanzur-docs' ) }
			className="itsdz-product-panel"
		>
			<ProductLinkPicker articleId={ postId } />
		</PluginDocumentSettingPanel>
	);
}

export function ProductDocsPanel() {
	const postType = useSelect(
		( select ) => select( editorStore ).getCurrentPostType(),
		[]
	);

	if ( postType !== ARTICLE_POST_TYPE ) {
		return null;
	}

	return <ProductDocsPanelFields />;
}
