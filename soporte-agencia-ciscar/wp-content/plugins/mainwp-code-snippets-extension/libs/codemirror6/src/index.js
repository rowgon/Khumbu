import { EditorState } from '@codemirror/state';
import { EditorView, crosshairCursor, drawSelection, dropCursor, highlightActiveLine, highlightActiveLineGutter, highlightSpecialChars, keymap, lineNumbers, rectangularSelection } from '@codemirror/view';
import { defaultKeymap, history, historyKeymap, indentWithTab } from '@codemirror/commands';
import { bracketMatching, codeFolding, defaultHighlightStyle, foldGutter, foldKeymap, indentOnInput, syntaxHighlighting } from '@codemirror/language';
import { autocompletion, closeBrackets, closeBracketsKeymap, completionKeymap } from '@codemirror/autocomplete';
import { oneDark } from '@codemirror/theme-one-dark';
import { php } from '@codemirror/lang-php';

let getLang = function (doc) {
	return /^\s*<\?(php|=)?/i.test(doc) ? php() : php({ plain: true });
};

document.addEventListener('DOMContentLoaded', () => {
	const textarea = document.getElementById('mainwp-code-snippets-code-editor');
	const editorParent = document.getElementById('mainwp-code-snippets-code-editor-holder');

	if (!textarea || !editorParent) {
		return;
	}

	textarea.style.display = 'none';

	const editorLayout = EditorView.theme({
		'&': {
			height: '600px'
		},
		'.cm-scroller': {
			overflow: 'auto'
		}
	});

	new EditorView({
		state: EditorState.create({
			doc: textarea.value,
			extensions: [
				lineNumbers(),
				foldGutter(),
				highlightActiveLineGutter(),
				highlightSpecialChars(),
				history(),
				codeFolding(),
				drawSelection(),
				dropCursor(),
				EditorView.lineWrapping,
				indentOnInput(),
				syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
				keymap.of([indentWithTab, ...defaultKeymap, ...historyKeymap, ...foldKeymap, ...completionKeymap, ...closeBracketsKeymap]),
				rectangularSelection(),
				crosshairCursor(),
				highlightActiveLine(),
				bracketMatching(),
				closeBrackets(),
				autocompletion(),
				getLang(textarea.value),
				oneDark,
				editorLayout,
				EditorView.updateListener.of((update) => {
					if (update.docChanged) {
						textarea.value = update.state.doc.toString();
					}
				}),
				EditorState.tabSize.of(4)
			]
		}),
		parent: editorParent
	});
});
