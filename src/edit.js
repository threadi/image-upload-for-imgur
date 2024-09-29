/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * Add individual dependencies.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { FormFileUpload, Spinner } from '@wordpress/components';
import apiFetch from "@wordpress/api-fetch";
import { BlockReplacer } from './BlockReplacer';
const { useSelect, dispatch } = wp.data;
const { useEffect } = wp.element;

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @param object
 * @return {WPElement} Element to render.
 */
export default function Edit( object ) {

	// secure id of this block
	useEffect(() => {
		object.setAttributes({blockId: object.clientId});
	});

  // get setting for multiple files.
  const imgur_allow_multiple_files = useSelect(
    ( select ) => select( 'core' ).getSite()?.imgur_allow_multiple_files,
    []
  );

  // get setting for types.
  const imgur_file_types = useSelect(
    ( select ) => select( 'core' ).getSite()?.imgur_file_types,
    []
  );

	/**
	 * Handle upload of images.
	 *
	 * @param files
	 * @returns {Promise<void>}
	 */
	const handleImagesUpload = async (files) => {
		try {
      /**
       * Get form data.
       *
       * @type {FormData}
       */
			const formData = new FormData();
      formData.append( 'post', wp.data.select("core/editor").getCurrentPostId() );
			let i = 0;
			Array.from(files).map(file => {
				i++;
				formData.append("file" + i, file);
			});

      /**
       * Show loading screen.
       */
      object.setAttributes( { spinner: true } )

      /**
       * Send form data.
       */
			await apiFetch({
				path: "imgur-image-upload/v1/files",
				method: "POST",
				body: formData,
			}).then((response) => {
        /**
         * Hide loading screen.
         */
        object.setAttributes( { spinner: false } )

        /**
         * Process the response: if the response contains an error: show it.
         */
        if( response.error ) {
          setError( response.error );
        }
        else {
          object.setAttributes( { images: response, error: '' } );
        }
      });
		} catch (error) {
      setError( error.message );
		}
	};

  /**
   * Add uploaded files as embed blocks, except the last one.
   */
  useEffect(() => {
    if( object.attributes.images.length > 1 ) {
      for( let i in object.attributes.images ){
        if( i < ( object.attributes.images.length - 1 ) ) {
          const block = wp.blocks.createBlock( 'core/embed', { url: object.attributes.images[i]} );
          dispatch( 'core/block-editor' ).insertBlocks( block );
        }
      }
    }
  })

  /**
   * Set error in object.
   *
   * @param error
   */
  function setError( error ) {
    object.setAttributes( { error: __( 'An error occurred:', 'imgur-image-upload' ) + ' ' + error } );
  }

  /**
   * Add custom class to our wrapper for styling.
   */
  const blockProps = useBlockProps( {
    className: 'imgur-image-upload-wrapper',
  } );

	/**
	 * Collect return for the edit-function.
	 */
	return (
		<div { ...blockProps }>
      {object.attributes.spinner && <Spinner />}
      {
        ! object.attributes.spinner && object.attributes.images.length === 0 && <FormFileUpload
					accept={imgur_file_types.map(e => e).join(',')}
					multiple={1 === imgur_allow_multiple_files}
					onChange={(event) => {handleImagesUpload(event.target.files) }	}
          className={"button"}
				>
					{__( 'Choose files to upload', 'imgur-image-upload' )}
				</FormFileUpload>
			}
      {! object.attributes.spinner && object.attributes.error.length > 0 && <p className={"error"}>{object.attributes.error}</p>}
			{! object.attributes.spinner && object.attributes.images.length > 0 && <BlockReplacer clientId={object.attributes.blockId} blockType={"core/embed"} attributes={ { "url": object.attributes.images[object.attributes.images.length-1] } } />}
		</div>
	);
}
