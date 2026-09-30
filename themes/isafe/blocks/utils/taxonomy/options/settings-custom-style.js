/**
 * Custom styling for Select2 control.
 */
const customStyles = {
	option: ( provided ) => ( {
		...provided,
		fontSize: '13px',
	} ),
	control: ( provided ) => ( {
		...provided,
		minHeight: '24px',
		borderColor: '#757575',
		marginBottom: '8px',
	} ),
	valueContainer: ( provided ) => ( {
		...provided,
		padding: '0 4px',
		minHeight: '28px',
	} ),
	input: ( provided ) => ( {
		...provided,
		margin: '0 2px',
	} ),
	singleValue: ( provided ) => ( {
		...provided,
		fontSize: '13px',
	} ),
	placeholder: ( provided ) => ( {
		...provided,
		fontSize: '13px',
		lineHeight: 'normal',
		fontFamily: 'system-ui',
	} ),
};

export default customStyles;
