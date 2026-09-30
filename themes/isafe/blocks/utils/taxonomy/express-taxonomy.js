/**
 * External dependencies
 */
import { useDebounce } from 'use-debounce';
import Select from 'react-select';
import CreatableSelect from 'react-select/creatable';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { dispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import customStyles from './options/settings-custom-style';

const CLASSNAME = 'components-mavero-taxonomy';

export default function MaveroTaxonomy(props) {
	const {
		label = __('Taxonomy', 'mavero'),
		subType = 'ingredient',
		isMulti = true,
		allowCreateNew = false,
	} = props;

	const [value, setValue] = useState(null);
	const [searchTerm, setSearchTerm] = useState('');
	const [debouncedSearchTerm] = useDebounce(searchTerm, 500);
	const [isDisabled, setIsDisabled] = useState(false);

	/**
	 * Get current post ID.
	 */
	const postId = useSelect(
		(select) => select('core/editor').getCurrentPostId(),
		[]
	);

	/**
	 * Get assigned terms of the taxonomy to current post.
	 */
	const assignedTerms = useSelect((select) => {
		const termsDetails = select('core').getEntityRecords('taxonomy', subType, {
			post: postId,
		});

		if (null !== termsDetails) {
			return termsDetails.map((term) => {
				return { value: term.id, label: term.name };
			});
		}
		/* eslint-disable-next-line react-hooks/exhaustive-deps */
	}, []) || null;

	useEffect(() => {
		if (!value) {
			setValue(assignedTerms);
		}
	}, [value, assignedTerms]);

	/**
	 * Fetch terms from the given taxonomy, based on search term.
	 */
	const allTerms = useSelect((select) => {
		const query = {
			per_page: 10,
			context: 'embed',
		};

		if (debouncedSearchTerm) {
			query.search = debouncedSearchTerm;
		}

		const attrs = select('core').getEntityRecords('taxonomy', subType, query);

		if (null !== attrs) {
			return attrs.map((term) => {
				const postTitle = term.name;
				return { value: term.id, label: postTitle };
			});
		}
	}, [debouncedSearchTerm, subType]) || null;

	/**
	 * Add a new term to the taxonomy. and assign to current post.
	 *
	 * @param {string} inputValue
	 */
	const createNewTerm = (inputValue) => {
		setIsDisabled(true);
		(async () => {
			try {
				const requestOptions = {
					path: `/wp/v2/${subType}`,
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({
						name: inputValue,
					}),
				};

				await apiFetch(requestOptions)
					.then((result) => {
						if ('object' === typeof result) {
							let newTerm = {
								value: result.id,
								label: result.name,
							};

							if (isMulti) {
								newTerm = [...value, newTerm];
							}

							onChange(newTerm);
						}
						setIsDisabled(false);
					});
			} catch (e) {
				setIsDisabled(false);
			}
		})();
	};

	const onChange = (updatedTerms) => {
		if (!isMulti) {
			updatedTerms = [updatedTerms];
		}

		setValue(updatedTerms);

		const termIds = updatedTerms.map((updatedTerm) => {
			return updatedTerm?.value;
		});

		const cleanIds = termIds.filter((termId) => termId);

		dispatch('core/editor').editPost({ [subType]: cleanIds }, postId);
	};

	const isLoading = !allTerms;

	const args = {
		className: `mavero-taxonomy--${subType}--select`,
		name: `field-${subType}--select`,
		value: value ?? null,
		placeholder: `Select ${label}...`,
		isLoading,
		options: allTerms ?? [],
		onChange,
		onInputChange: setSearchTerm,
		styles: customStyles,
		isMulti,
		isDisabled,
		isClearable: true,
	};

	return (
		<div className={`${CLASSNAME} mavero-taxonomy--${subType}`}>
			<label
				className={`${CLASSNAME}--${subType}-label components-input-control__label`}
				htmlFor={`mavero-taxonomy--${subType}-select`}
			>
				{label}
			</label>
			{allowCreateNew
				? (
					<CreatableSelect
						{...args}
						onCreateOption={createNewTerm}
					/>
				) : (
					<Select
						{...args}
					/>
				)}
		</div>
	);
}
