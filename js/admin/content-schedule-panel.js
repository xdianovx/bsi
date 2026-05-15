(function (wp) {
  if (!wp || !wp.plugins || !wp.editPost || !wp.element || !wp.data || !wp.components) {
    return;
  }

  var config = window.bsiContentSchedulePanel || {};
  var postTypes = config.postTypes || [];
  var keysByType = config.keysByType || {};
  var hint = config.hint || '';

  var registerPlugin = wp.plugins.registerPlugin;
  var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
  var useSelect = wp.data.useSelect;
  var useDispatch = wp.data.useDispatch;
  var createElement = wp.element.createElement;
  var TextControl = wp.components.TextControl;

  function ymdToInput(ymd) {
    if (!ymd || String(ymd).length !== 8) {
      return '';
    }
    return String(ymd).slice(0, 4) + '-' + String(ymd).slice(4, 6) + '-' + String(ymd).slice(6, 8);
  }

  function inputToYmd(value) {
    if (!value) {
      return '';
    }
    return String(value).replace(/-/g, '');
  }

  function SchedulePanel() {
    var postType = useSelect(function (select) {
      return select('core/editor').getCurrentPostType();
    });

    var meta = useSelect(function (select) {
      return select('core/editor').getEditedPostAttribute('meta') || {};
    });

    var editPost = useDispatch('core/editor').editPost;

    if (!postType || postTypes.indexOf(postType) === -1) {
      return null;
    }

    var keys = keysByType[postType];
    if (!keys) {
      return null;
    }

    var fromKey = keys.from;
    var untilKey = keys.until;

    return createElement(
      PluginDocumentSettingPanel,
      {
        name: 'bsi-content-schedule-panel',
        title: 'Срок показа на сайте',
        className: 'bsi-content-schedule-panel',
      },
      createElement(TextControl, {
        label: 'Показывать с',
        type: 'date',
        value: ymdToInput(meta[fromKey]),
        onChange: function (value) {
          var patch = {};
          patch[fromKey] = inputToYmd(value);
          editPost({ meta: patch });
        },
      }),
      createElement(TextControl, {
        label: 'Показывать до',
        type: 'date',
        value: ymdToInput(meta[untilKey]),
        onChange: function (value) {
          var patch = {};
          patch[untilKey] = inputToYmd(value);
          editPost({ meta: patch });
        },
      }),
      hint
        ? createElement(
            'p',
            { className: 'bsi-content-schedule-panel__hint' },
            hint
          )
        : null
    );
  }

  registerPlugin('bsi-content-schedule', {
    render: SchedulePanel,
    icon: null,
  });
})(window.wp);
