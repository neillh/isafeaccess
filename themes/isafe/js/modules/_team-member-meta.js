/**
 * "Team Member" panel in the document sidebar for the `team` post type.
 *
 * Edits the post meta registered in PHP — see
 * Mavero\Components\Post_Types\Team::register_meta(). Position is displayed on
 * the front end by the "Team Position" core/paragraph variation; all fields
 * feed the Person schema node.
 */

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel, store as editorStore } from '@wordpress/editor';
import { BaseControl, Button, Flex, FlexBlock, FlexItem, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { closeSmall, plus } from '@wordpress/icons';

const POST_TYPE = 'team';
const META_POSITION = 'team_position';
const META_LINKEDIN = 'team_linkedin';
const META_CREDENTIALS = 'team_credentials';
const META_KNOWS_ABOUT = 'team_knows_about';

const ROW_STYLE = {
	marginBottom: '8px',
	padding: '8px',
	border: '1px solid #ddd',
	borderRadius: '2px',
};

/**
 * Remove button for a repeater row.
 *
 * @param {Object}   props         Component props.
 * @param {string}   props.label   Accessible label.
 * @param {Function} props.onClick Click handler.
 * @return {Element} Button.
 */
const RemoveButton = ( { label, onClick } ) => (
	<Button icon={ closeSmall } label={ label } size="small" isDestructive onClick={ onClick } />
);

/**
 * Repeater for credentials: each row has a name and an issuing body.
 *
 * @param {Object}   props          Component props.
 * @param {Array}    props.value    Credential rows.
 * @param {Function} props.onChange Called with the new rows.
 * @return {Element} Control.
 */
const CredentialsControl = ( { value, onChange } ) => {
	const update = ( index, field, fieldValue ) =>
		onChange( value.map( ( row, i ) => ( i === index ? { ...row, [ field ]: fieldValue } : row ) ) );

	return (
		<BaseControl
			__nextHasNoMarginBottom
			id="mavero-team-credentials"
			label={ __( 'Credentials', 'mavero' ) }
			help={ __( 'Licences, certifications and qualifications.', 'mavero' ) }
		>
			{ value.map( ( row, index ) => (
				<div key={ index } style={ ROW_STYLE }>
					<Flex align="flex-start">
						<FlexBlock>
							<TextControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								label={ __( 'Name', 'mavero' ) }
								placeholder={ __( 'e.g. High Risk Work Licence', 'mavero' ) }
								value={ row.name ?? '' }
								onChange={ ( name ) => update( index, 'name', name ) }
							/>
							<div style={ { height: '8px' } } />
							<TextControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								label={ __( 'Issued by', 'mavero' ) }
								placeholder={ __( 'e.g. SafeWork NSW', 'mavero' ) }
								value={ row.issuer ?? '' }
								onChange={ ( issuer ) => update( index, 'issuer', issuer ) }
							/>
						</FlexBlock>
						<FlexItem>
							<RemoveButton
								/* translators: %d: credential number. */
								label={ sprintf( __( 'Remove credential %d', 'mavero' ), index + 1 ) }
								onClick={ () => onChange( value.filter( ( _, i ) => i !== index ) ) }
							/>
						</FlexItem>
					</Flex>
				</div>
			) ) }
			<Button
				variant="secondary"
				icon={ plus }
				onClick={ () => onChange( [ ...value, { name: '', issuer: '' } ] ) }
			>
				{ __( 'Add credential', 'mavero' ) }
			</Button>
		</BaseControl>
	);
};

/**
 * Repeater for areas of expertise: one text field per topic.
 *
 * @param {Object}   props          Component props.
 * @param {Array}    props.value    Topics.
 * @param {Function} props.onChange Called with the new topics.
 * @return {Element} Control.
 */
const KnowsAboutControl = ( { value, onChange } ) => (
	<BaseControl
		__nextHasNoMarginBottom
		id="mavero-team-knows-about"
		label={ __( 'Areas of expertise', 'mavero' ) }
		help={ __( 'Topics this person is an expert in.', 'mavero' ) }
	>
		{ value.map( ( topic, index ) => (
			<Flex key={ index } style={ { marginBottom: '8px' } }>
				<FlexBlock>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						/* translators: %d: topic number. */
						label={ sprintf( __( 'Topic %d', 'mavero' ), index + 1 ) }
						hideLabelFromVision
						placeholder={ __( 'e.g. Roof anchor points', 'mavero' ) }
						value={ topic }
						onChange={ ( text ) => onChange( value.map( ( t, i ) => ( i === index ? text : t ) ) ) }
					/>
				</FlexBlock>
				<FlexItem>
					<RemoveButton
						/* translators: %d: topic number. */
						label={ sprintf( __( 'Remove topic %d', 'mavero' ), index + 1 ) }
						onClick={ () => onChange( value.filter( ( _, i ) => i !== index ) ) }
					/>
				</FlexItem>
			</Flex>
		) ) }
		<Button variant="secondary" icon={ plus } onClick={ () => onChange( [ ...value, '' ] ) }>
			{ __( 'Add topic', 'mavero' ) }
		</Button>
	</BaseControl>
);

/**
 * Sidebar panel with the team member's meta fields.
 *
 * @return {Element|null} Panel, or null outside the team post type.
 */
const TeamMemberMetaPanel = () => {
	const { postType, postId } = useSelect( ( select ) => {
		const { getCurrentPostType, getCurrentPostId } = select( editorStore );
		return { postType: getCurrentPostType(), postId: getCurrentPostId() };
	}, [] );
	const [ meta, setMeta ] = useEntityProp( 'postType', POST_TYPE, 'meta', postId );

	if ( POST_TYPE !== postType ) {
		return null;
	}

	const updateMeta = ( key, value ) => setMeta( { ...meta, [ key ]: value } );

	return (
		<PluginDocumentSettingPanel
			name="mavero-team-member"
			title={ __( 'Team Member', 'mavero' ) }
		>
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Position', 'mavero' ) }
				placeholder={ __( 'e.g. Director', 'mavero' ) }
				value={ meta?.[ META_POSITION ] ?? '' }
				onChange={ ( value ) => updateMeta( META_POSITION, value ) }
			/>
			<div style={ { height: '16px' } } />
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				type="url"
				label={ __( 'LinkedIn URL', 'mavero' ) }
				placeholder="https://www.linkedin.com/in/…"
				value={ meta?.[ META_LINKEDIN ] ?? '' }
				onChange={ ( value ) => updateMeta( META_LINKEDIN, value ) }
			/>
			<div style={ { height: '16px' } } />
			<CredentialsControl
				value={ meta?.[ META_CREDENTIALS ] ?? [] }
				onChange={ ( value ) => updateMeta( META_CREDENTIALS, value ) }
			/>
			<div style={ { height: '16px' } } />
			<KnowsAboutControl
				value={ meta?.[ META_KNOWS_ABOUT ] ?? [] }
				onChange={ ( value ) => updateMeta( META_KNOWS_ABOUT, value ) }
			/>
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'mavero-team-member-meta', { render: TeamMemberMetaPanel } );
