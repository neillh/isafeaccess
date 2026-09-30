/**
 * WordPress dependencies
 */
import { Button, ButtonGroup, PanelBody, Flex, FlexItem } from '@wordpress/components';
function ButtonGroupController( { selectedOption, options, onClick, title, isSmall = false, children = null } ) {
	return (
		<PanelBody title={ title }>
			<Flex gap={ '1rem' } direction={ 'column' }>
				<FlexItem>
					<ButtonGroup aria-label={ title }>
						{ options.map( ( option ) => {
							return (
								<Button
									key={ option.name }
									isSmall={ isSmall }
									variant={
										option.name === selectedOption
											? 'primary'
											: undefined
									}
									onClick={ () => onClick( option.name ) }
								>
									{ option.label }
								</Button>
							);
						} ) }
					</ButtonGroup>
				</FlexItem>
				{ children && ( <FlexItem>{ children }</FlexItem> ) }
			</Flex>
		</PanelBody>
	);
}

export default ButtonGroupController;
