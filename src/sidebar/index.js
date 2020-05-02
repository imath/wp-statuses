const { registerPlugin } = wp.plugins;
const { PluginPostStatusInfo } = wp.editPost;
const { createElement, Component } = wp.element;
const { __, sprintf } = wp.i18n;
const { withSelect, withDispatch, registerStore } = wp.data;
const { get, indexOf, forEach, map } = lodash;
const { SelectControl, TextControl } = wp.components;
const { compose } = wp.compose;
const { apiFetch } = wp;
const { synchronizeBlocksWithTemplate } = wp.blocks;

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

class WPStatusesPanel extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			isValid: false,
		}
	}

	componentDidMount() {
		const { settings, currentPost, customStatus, insertBlocks, onUpdateStatus } = this.props;
		const { isValid } = this.state;

		if ( ! isValid && 'auto-draft' === customStatus && ! currentPost.content ) {
			const template = synchronizeBlocksWithTemplate( [], settings.template );

			if ( template && template.length >= 1 ) {
				insertBlocks( template );
			}

			this.setState( { isValid: true } );
		}
	}

	render() {
		const {
			onUpdateStatus,
			postType,
			currentPost,
			stati,
			password,
			postTitle,
			customStatus
		} = this.props;
		let { currentStatus } = this.props;

		const needsPassword = 'password' === currentStatus;
		const hasPublishAction = get( currentPost, [ '_links', 'wp:action-publish' ], false );

		if ( 'future' === currentPost.status && 'future' !== currentStatus ) {
			currentStatus = 'future';
			onUpdateStatus( currentStatus );
		}

		if ( 'future' === currentStatus && stati.publish ) {
			return (
				<PluginPostStatusInfo className="wp-statuses-info">
					{ sprintf( __( 'Next status will be "%s" once the scheduled date will be reached.', 'wp-statuses' ), stati.publish.label ) }
				</PluginPostStatusInfo>
			);
		}

		// As the draft status is selected by default, let's avoid saving auto-drafts.
		if ( postTitle !== currentPost.title && 'auto-draft' === customStatus ) {
			onUpdateStatus( currentStatus );
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
					value={ currentStatus }
					onChange={ ( status ) => onUpdateStatus( status ) }
					options={ options }
				/>

				{ needsPassword &&
					<TextControl
						label={ __( 'Password', 'wp-statuses' ) }
						value={ password }
						className="wp-statuses-password"
						onChange={ ( password ) => onUpdateStatus( currentStatus, password ) }
					/>
				}
			</PluginPostStatusInfo>
		);
	}
}

const WPStatusesInfo = compose( [
	withSelect( ( select ) => {
		const { getEditedPostAttribute, getCurrentPost } = select( 'core/editor' );
		const { getPostType } = select( 'core' );
		const postTypeName = getEditedPostAttribute( 'type' );
		const stati = select( 'wp-statuses' ).getStati();
		const customStatus = getEditedPostAttribute( 'custom_status' );

		return {
			postType: getPostType( postTypeName ),
			customStatus: customStatus,
			currentStatus: ! customStatus || 'auto-draft' === customStatus ? 'draft' : customStatus,
			currentPost: getCurrentPost(),
			stati: stati,
			password: getEditedPostAttribute( 'password' ),
			settings: select( 'core/editor' ).getEditorSettings(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { editPost, resetEditorBlocks } = dispatch( 'core/editor' );

		return {
			onUpdateStatus: ( WPStatusesStatus, password = '' ) => {
				editPost( {
					custom_status: WPStatusesStatus,
					password: password,
				} );
			},
			insertBlocks: ( template ) => {
				resetEditorBlocks( template );
			}
		};
	} ),
] )( WPStatusesPanel );

registerPlugin( 'wp-statuses-sidebar', {
	render: WPStatusesInfo,
} );
