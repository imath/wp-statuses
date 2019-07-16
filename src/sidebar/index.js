const { registerPlugin } = wp.plugins;
const { PluginPostStatusInfo } = wp.editPost;
const { createElement } = wp.element;
const { __, _x, _n, _nx } = wp.i18n;
const { withSelect, withDispatch } = wp.data;
const { indexOf, forEach } = lodash;
const { SelectControl } = wp.components;
const { compose } = wp.compose;
const { apiFetch } = wp;

let wpStati = [];

apiFetch( { path: '/wp/v2/statuses?context=edit' } ).then( stati => {
    forEach( stati, function ( data ) {
        wpStati.push( { label: data.name, value: data.slug, post_types: data.post_type } );
    } );
} );

function WPStatusesPanel( { onUpdateStatus, postType, status = 'draft' } ) {
    let options = [];

    if ( postType && postType.slug ) {
        forEach( wpStati, function( data, i ) {
            if ( -1 !== indexOf( data.post_types, postType.slug ) ) {
                options.push( { label: data.label, value: data.value } );
            }
        } );
    }

	return (
		<PluginPostStatusInfo
			className="wp-statuses-info"
		>
			<SelectControl
				label={ __( 'Status', 'wp-statuses' ) }
				value={ status }
				onChange={ ( status ) => onUpdateStatus( status ) }
				options={ options }
			/>
		</PluginPostStatusInfo>
	);
};

const WPStatusesInfo = compose( [
	withSelect( ( select ) => {
		const { getEditedPostAttribute } = select( 'core/editor' );
		const { getPostType } = select( 'core' );
		const postTypeName = getEditedPostAttribute( 'type' );

		return {
			postType: getPostType( postTypeName ),
			status: getEditedPostAttribute( 'status' ),
		};
	} ),
	withDispatch( ( dispatch ) => ( {
		onUpdateStatus( WPStatusesStatus ) {
			dispatch( 'core/editor' ).editPost( { status: WPStatusesStatus } );
		},
	} ) ),
] )( WPStatusesPanel );

registerPlugin( 'wp-statuses-sidebar', {
	render: WPStatusesInfo,
} );
