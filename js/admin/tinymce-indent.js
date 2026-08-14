/**
 * Кнопка «Красная строка» для классического редактора (TinyMCE).
 *
 * Вешает на абзац класс is-style-indent. Стили — scss/components/editor-styles.scss
 * (фронт) и assets/css/editor-style.css (превью в редакторе).
 * Регистрация кнопки — inc/admin/paragraph-indent.php.
 */
(function () {
  "use strict";

  tinymce.PluginManager.add("bsi_indent", function (editor) {
    var FORMAT = "bsi_indent";
    var SHORTCUT = "access+z";

    editor.on("init", function () {
      editor.formatter.register(FORMAT, {
        block: "p",
        classes: "is-style-indent",
      });
    });

    function toggle() {
      editor.formatter.toggle(FORMAT);
      editor.nodeChanged();
    }

    editor.addCommand("bsiToggleIndent", toggle);
    editor.addShortcut(SHORTCUT, "Красная строка", "bsiToggleIndent");

    editor.addButton("bsi_indent", {
      icon: "bsi-indent",
      tooltip: "Красная строка (Alt+Shift+Z)",
      cmd: "bsiToggleIndent",
      onPostRender: function () {
        var button = this;

        editor.on("init", function () {
          editor.formatter.formatChanged(FORMAT, function (state) {
            button.active(state);
          });
        });
      },
    });
  });
})();
