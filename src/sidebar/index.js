const { registerPlugin } = wp.plugins;
const { PluginPostStatusInfo } = wp.editPost;
const { createElement } = wp.element;
const { __, _x, _n, _nx } = wp.i18n;
const { withSelect, withDispatch, registerStore } = wp.data;
const { get, indexOf, forEach } = lodash;
const { SelectControl } = wp.components;
const { compose } = wp.compose;
const { apiFetch } = wp;

const DEFAULT_STATE = {
    stati: {},
}

const actions = {
	getStati( stati ) {
		return {
			type: 'GET_STATI',
			stati,
		};
	},

	fetchFromAPI( path ) {
		return {
			type: 'FETCH_FROM_API',
			path,
		};
	},
};

registerStore( 'wp-statuses', {
	reducer( state = DEFAULT_STATE, action ) {
		if ( 'GET_STATI' === action.type ) {
            return {
                ...state,
                stati: action.stati,
            };
		}

		return state;
	},

	actions,

	selectors: {
		getStati( state ) {
			return state.stati;
		},
	},

	controls: {
		FETCH_FROM_API( action ) {
			return apiFetch( { path: action.path } );
		},
	},

	resolvers: {
		* getStati() {
			const path = '/wp/v2/statuses?context=edit';
			const stati = yield actions.fetchFromAPI( path );
			return actions.getStati( stati );
		},
	},
} );

function WPStatusesPanel( { onUpdateStatus, postType, status = 'draft', hasPublishAction, stati } ) {
	let options = [];

	if ( postType && postType.slug ) {
        forEach( stati, function( data ) {
            if ( -1 !== indexOf( data.post_type, postType.slug ) && ( hasPublishAction || -1 !== indexOf( ['draft', 'pending'], data.slug ) ) ) {
                options.push( { label: data.name, value: data.slug } );
            }
		} );
	}

	return (
		<PluginPostStatusInfo className="wp-statuses-info">
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
		const { getEditedPostAttribute, getCurrentPost } = select( 'core/editor' );
		const { getPostType } = select( 'core' );
		const postTypeName = getEditedPostAttribute( 'type' );
		const stati = select( 'wp-statuses' ).getStati();

		return {
			postType: getPostType( postTypeName ),
			status: getEditedPostAttribute( 'custom_status' ),
			hasPublishAction: get( getCurrentPost(), [ '_links', 'wp:action-publish' ], false ),
			stati: stati,
		};
	} ),
	withDispatch( ( dispatch ) => ( {
		onUpdateStatus( WPStatusesStatus ) {
			dispatch( 'core/editor' ).editPost( { custom_status: WPStatusesStatus } );
		},
	} ) ),
] )( WPStatusesPanel );

registerPlugin( 'wp-statuses-sidebar', {
	render: WPStatusesInfo,
} );
