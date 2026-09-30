export default ( setMeta, meta ) => (
	metaField,
	callback = () => {
	},
	allowUndefined = false
) => ( newValue ) => {
	const returnValue = callback( newValue );

	if ( returnValue !== undefined || allowUndefined ) {
		newValue = returnValue;
	}

	setMeta( { ...meta, [ metaField ]: newValue } );
};
