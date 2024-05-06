
import $ from 'jquery';
import * as Modals from '@typo3/form/backend/form-editor/modals-component.js';
import * as Helper from '@typo3/form/backend/form-editor/helper.js'
import Severity from '@typo3/backend/severity.js';
import Modal from '@typo3/backend/modal.js';


const {
  bootstrap,
  showMessageEditorModal,
} = factory($, Helper, Modal, Modals, Severity);

export {
  bootstrap,
  showMessageEditorModal,
};

function factory($, Helper, Modal, Modals, Severity) {
  return (function($, Helper, Modal, Modals, Severity) {


    var _formEditorApp = null;


    /**
     * @private
     *
     * @var object
     */
    var _configuration = null;


    var _defaultConfiguration = {
      domElementClassNames: {
        buttonDefault: 'btn-default',
        buttonInfo: 'btn-info',
        buttonWarning: 'btn-warning'
      },
      domElementDataAttributeNames: {
        elementType: 'element-type',
        fullElementType: 'data-element-type'
      },
      domElementDataAttributeValues: {
        templateMessageEditor: 'Modal-MessageEditor'
      }
    };



    function _helperSetup() {
      assert('function' === $.type(Helper.bootstrap),
        'The view model helper does not implement the method "bootstrap"',
        1478268638
      );
      Helper.bootstrap(getFormEditorApp());
      Helper.setConfiguration({
        domElementDataAttributeValues: {
          buttonStageRichtextEditor: 'stageRichtextEditor',
          templateMessageEditor: 'Modal-MessageEditor',
        }
      });
    };



    function showMessageEditorModal(publisherTopicName, callback) {
      var html, template;


      template = Helper.getTemplate('templateMessageEditor');
      if (template.length > 0) {
        html = $(template.html());
        _insertElementsModalSetup(html, publisherTopicName);

        Modal.advanced({
          title: 'eddeeded',
          content: $(html),
          size: 'large',
          severity: Severity.info,
          staticBackdrop: true,
          buttons: [{
            text: top.TYPO3.lang['cm.save'],
            active: true,
            btnClass: 'btn-primary float-start',
            name: 'save',
            trigger: function(e, modal) {
              getPublisherSubscriber().publish('view/modal/close/perform', []);
              modal.hideModal();
            }
          }],
          callback: callback,
          additionalCssClasses: ['modal-multi-step-wizard']
        });
      }
    };


    /**
     * @private
     *
     * @param object modalContent
     * @param string publisherTopicName
     * @param object configuration
     * @return void
     * @publish mixed
     * @throws 1478910954
     */
    function _insertElementsModalSetup(modalContent, publisherTopicName, configuration) {
      var formElementItems;

      assert(
        getUtility().isNonEmptyString(publisherTopicName),
        'Invalid parameter "publisherTopicName"',
        1478910954
      );

      if ('object' === $.type(configuration)) {
        for (var key in configuration) {
          if (!configuration.hasOwnProperty(key)) {
            continue;
          }
          if (
            key === 'disableElementTypes'
            && 'array' === $.type(configuration[key])
          ) {
            for (var i = 0, len = configuration[key].length; i < len; ++i) {
              $(
                getHelper().getDomElementDataAttribute(
                  'fullElementType',
                  'bracesWithKeyValue', [configuration[key][i]]
                ),
                modalContent
              ).addClass(getHelper().getDomElementClassName('disabled'));
            }
          }

          if (
            key === 'onlyEnableElementTypes'
            && 'array' === $.type(configuration[key])
          ) {
            $(
              getHelper().getDomElementDataAttribute(
                'fullElementType',
                'bracesWithKey'
              ),
              modalContent
            ).each(function(i, element) {
              for (var i = 0, len = configuration[key].length; i < len; ++i) {
                var that = $(this);
                if (that.data(getHelper().getDomElementDataAttribute('elementType')) !== configuration[key][i]) {
                  that.addClass(getHelper().getDomElementClassName('disabled'));
                }
              }
            });
          }
        }
      }

      $('a', modalContent).on("click", function(e) {
        getPublisherSubscriber().publish(publisherTopicName, [$(this).data(getHelper().getDomElementDataAttribute('elementType'))]);
        $('a', modalContent).off();
        Modal.currentModal.hideModal();
      });
    };


    /**
     * @private
     *
     * @return object
     */
    function getFormEditorApp() {
      return _formEditorApp;
    };

    /**
     * @public
     *
     * @param object
     * @return object
     */
    function getHelper(configuration) {
      if (getUtility().isUndefinedOrNull(configuration)) {
        return Helper.setConfiguration(_configuration);
      }
      return Helper.setConfiguration(configuration);
    };


    /**
     * @private
     *
     * @param mixed test
     * @param string message
     * @param int messageCode
     * @return void
     */
    function assert(test, message, messageCode) {
      return getFormEditorApp().assert(test, message, messageCode);
    };


    /**
     * @private
     *
     * @return object
     */
    function getUtility() {
      return getFormEditorApp().getUtility();
    };


    function bootstrap(formEditorApp, configuration) {
      _formEditorApp = formEditorApp;
      _configuration = $.extend(true, _defaultConfiguration, configuration || {});
      _helperSetup();
      return this;
    };


    return {
      bootstrap: bootstrap,
      showMessageEditorModal: showMessageEditorModal
    };
  })($, Helper, Modal, Modals, Severity);
}

