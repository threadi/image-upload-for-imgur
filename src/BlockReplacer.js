/**
 * File to handle block replacement.
 */

import { store as blockEditorStore } from '@wordpress/block-editor'
import { createBlock } from '@wordpress/blocks'
import { useSelect, useDispatch } from '@wordpress/data'
import { useEffect } from '@wordpress/element'

/**
 * The BlockReplaced object.
 *
 * @param clientId
 * @param blockType
 * @param attributes
 * @constructor
 */
export const BlockReplacer = ({ clientId, blockType, attributes }) => {
    const block = useSelect(
        (select) => select(blockEditorStore).getBlock(clientId ?? ''),
        [clientId],
    )
    const { replaceBlock } = useDispatch(blockEditorStore)
    useEffect(() => {
        if (!block?.name || !replaceBlock || !clientId) return
        replaceBlock(clientId, [createBlock(blockType, attributes)])
    }, [block, replaceBlock, clientId, blockType])
}
