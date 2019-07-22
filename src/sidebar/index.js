const { registerPlugin } = wp.plugins;
const { PluginPostStatusInfo } = wp.editPost;
const { createElement } = wp.element;
const { __, sprintf } = wp.i18n;
const { withSelect, withDispatch, registerStore } = wp.data;
const { get, indexOf, forEach } = lodash;
const { SelectControl, TextControl } = wp.components;
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

function WPStatusesPanel( { onUpdateStatus, postType, custom_status = 'draft', currentPost, stati, password } ) {
	const needsPassword    = 'password' === custom_status;
	const hasPublishAction = get( currentPost, [ '_links', 'wp:action-publish' ], false );

	if ( 'future' === currentPost.status && 'future' !== custom_status ) {
		custom_status = 'future';
		onUpdateStatus( custom_status );
	}

	if ( 'future' === custom_status && stati.publish ) {
		return (
			<PluginPostStatusInfo className="wp-statuses-info">
				{ sprintf( __( 'Next status will be "%s" once the scheduled date will be reached.', 'wp-statuses' ), stati.publish.label ) }
			</PluginPostStatusInfo>
		);
	}

	let options = [];
	if ( postType && postType.slug ) {
        forEach( stati, function( data ) {
            if ( -1 !== indexOf( data.post_type, postType.slug ) && ( hasPublishAction || -1 !== indexOf( ['draft', 'pending'], data.slug ) ) ) {
                options.push( { label: data.label, value: data.slug } );
            }
		} );
	}

	return (
		<PluginPostStatusInfo className="wp-statuses-info">
			<SelectControl
				label={ __( 'Status', 'wp-statuses' ) }
				value={ custom_status }
				onChange={ ( custom_status ) => onUpdateStatus( custom_status ) }
				options={ options }
			/>

			{ needsPassword &&
				<TextControl
					label={ __( 'Password', 'wp-statuses' ) }
					value={ password }
					className="wp-statuses-password"
					onChange={ ( password ) => onUpdateStatus( custom_status, password ) }
				/>
			}
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
			custom_status: getEditedPostAttribute( 'custom_status' ),
			currentPost: getCurrentPost(),
			stati: stati,
			password: getEditedPostAttribute( 'password' ),
		};
	} ),
	withDispatch( ( dispatch ) => ( {
		onUpdateStatus( WPStatusesStatus, password = '' ) {
			dispatch( 'core/editor' ).editPost( {
				custom_status: WPStatusesStatus,
				password: password,
			} );
		},
	} ) ),
] )( WPStatusesPanel );

registerPlugin( 'wp-statuses-sidebar', {
	render: WPStatusesInfo,
} );
