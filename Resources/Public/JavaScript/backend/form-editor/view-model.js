/**
 * Module: @my-vendor/my-site-package/backend/form-editor/view-model.js
 */
import $ from 'jquery';
import * as Helper from '@typo3/form/backend/form-editor/helper.js'
import Modal from '@typo3/backend/modal.js';
import Severity from '@typo3/backend/severity.js';
import Icons from '@typo3/backend/icons.js';
import * as ExtendedModals from './extended-modals.js';


/**
 * @private
 *
 * @var object
 */
let _formEditorApp = null;


/**
 * @public
 *
 * @param object formEditorApp
 * @return void
 */
export function bootstrap(formEditorApp) {
  _formEditorApp = formEditorApp;
  _updateConfiguration();
  _helperSetup();
  _modalsSetup();
  _subscribeEvents();

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
 * @private
 *
 * @return object
 */
function getPublisherSubscriber() {
  return getFormEditorApp().getPublisherSubscriber();
};

/**
 * @private
 *
 * @return object
 */
function getUtility() {
  return getFormEditorApp().getUtility();
};

/**
 * @private
 *
 * @param object
 * @return object
 */
function getHelper() {
  return Helper;
};

function getModals() {
  return ExtendedModals;
};

/**
 * @private
 *
 * @return object
 */
function getCurrentlySelectedFormElement() {
  return getFormEditorApp().getCurrentlySelectedFormElement();
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
 * @return void
 * @throws 1491643380
 */
function _helperSetup() {
  assert('function' === $.type(Helper.bootstrap),
    'The view model helper does not implement the method "bootstrap"',
    1491643380
  );
  Helper.bootstrap(getFormEditorApp());
};


function _modalsSetup() {
  assert('function' === $.type(ExtendedModals.bootstrap),
    'The modals component does not implement the method "bootstrap"',
    1491643380
  );
  ExtendedModals.bootstrap(getFormEditorApp(),{
    domElementDataAttributeValues: {
      buttonStageRichtextEditor: 'stageRichtextEditor',
      templateMessageEditor: 'Modals-MessageEditor',
    }
  });
};

/**
 * @private
 *
 * @return void
 */
function _subscribeEvents() {
  getPublisherSubscriber().subscribe('view/stage/abstract/render/template/perform', function (topic, args) {
    _renderTemplateDispatcherFormExtended(args[0], args[1]);
  });
  getPublisherSubscriber().subscribe('view/inspector/editor/insert/perform', function (topic, args) {
    _renderEditorDispatcherFormExtended(args[0], args[1], args[2], args[3]);
  });
  getPublisherSubscriber().subscribe('view/stage/abstract/button/openMessageEditor/clicked', function (topic, args) {

    getModals().showMessageEditorModal('view/modal/messageEditor/perform', initRTE);

  });

}

function initRTE() {
  console.debug('initRTE');
};

/**
 * @private
 *
 * @return void
 */
function _renderTemplateDispatcherFormExtended(formElement, template) {
  //console.debug(formElement.get('type'));
  switch (formElement.get('type')) {
    case 'PrivacyPolicyCheckbox':
      getFormEditorApp().getViewModel().getStage().renderSimpleTemplateWithValidators(formElement, template);
      break;
  }
};

/**
 * @private
 *
 * @return void
 */
function _renderEditorDispatcherFormExtended(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
  //console.debug(editorConfiguration['templateName']);

  switch (editorConfiguration['templateName']) {
    case 'Inspector-StaticTextEditor':
      renderStaticTextEditor(
        editorConfiguration,
        editorHtml,
        collectionElementIdentifier,
        collectionName
      );
      break;
    case 'Inspector-ModalTextareaEditor':
      renderModalTextareaEditor(
        editorConfiguration,
        editorHtml,
        collectionElementIdentifier,
        collectionName
      );
      break;
  }
};

function _updateConfiguration() {


  getHelper().setConfiguration({
    domElementDataAttributeValues: {
      buttonStageRichtextEditor: 'stageRichtextEditor',
      templateMessageEditor: 'Modals-MessageEditor',
    }
  });



}




/**
 * @public
 *
 * @param object editorConfiguration
 * @param object editorHtml
 * @param string collectionElementIdentifier
 * @param string collectionName
 * @return void
 * @throws 1475421053
 * @throws 1475421054
 * @throws 1475421055
 * @throws 1475421056
 */
function renderStaticTextEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
  var propertyData, propertyPath;
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475421053
  );
  assert(
    'object' === $.type(editorHtml),
    'Invalid parameter "editorHtml"',
    1475421054
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration['label']),
    'Invalid configuration "label"',
    1475421055
  );


  getHelper()
    .getTemplatePropertyDomElement('label', editorHtml)
    .append(editorConfiguration['label']);
  if (getUtility().isNonEmptyString(editorConfiguration['fieldExplanationText'])) {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .text(editorConfiguration['fieldExplanationText']);
  } else {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .remove();
  }

  if (getUtility().isNonEmptyString(editorConfiguration['placeholder'])) {
    getHelper()
      .getTemplatePropertyDomElement('propertyPath', editorHtml)
      .attr('placeholder', editorConfiguration['placeholder']);
  }

  if (editorConfiguration['propertyPath']  !== undefined) {
    propertyPath = getFormEditorApp().buildPropertyPath(
      editorConfiguration['propertyPath'],
      collectionElementIdentifier,
      collectionName
    );
    propertyData = getCurrentlySelectedFormElement().get(propertyPath);

    //getFormEditorApp().getInspector().getEditor().getValidator()._validateCollectionElement(propertyPath, editorHtml);
    //_validateCollectionElement(propertyPath, editorHtml);

    getHelper().getTemplatePropertyDomElement('propertyPath', editorHtml).text(propertyData);
  }




  return;
  if (
    !getUtility().isUndefinedOrNull(editorConfiguration['additionalElementPropertyPaths'])
    && 'array' === $.type(editorConfiguration['additionalElementPropertyPaths'])
  ) {
    for (var i = 0, len = editorConfiguration['additionalElementPropertyPaths'].length; i < len; ++i) {
      getCurrentlySelectedFormElement().set(editorConfiguration['additionalElementPropertyPaths'][i], propertyData);
    }
  }

  renderFormElementSelectorEditorAddition(editorConfiguration, editorHtml, propertyPath);

  getHelper().getTemplatePropertyDomElement('propertyPath', editorHtml).on('keyup paste', function() {
    if (
      !!editorConfiguration['doNotSetIfPropertyValueIsEmpty']
      && !getUtility().isNonEmptyString($(this).val())
    ) {
      getCurrentlySelectedFormElement().unset(propertyPath);
    } else {
      getCurrentlySelectedFormElement().set(propertyPath, $(this).val());
    }
    _validateCollectionElement(propertyPath, editorHtml);
    if (
      !getUtility().isUndefinedOrNull(editorConfiguration['additionalElementPropertyPaths'])
      && 'array' === $.type(editorConfiguration['additionalElementPropertyPaths'])
    ) {
      for (var i = 0, len = editorConfiguration['additionalElementPropertyPaths'].length; i < len; ++i) {
        if (
          !!editorConfiguration['doNotSetIfPropertyValueIsEmpty']
          && !getUtility().isNonEmptyString($(this).val())
        ) {
          getCurrentlySelectedFormElement().unset(editorConfiguration['additionalElementPropertyPaths'][i]);
        } else {
          getCurrentlySelectedFormElement().set(editorConfiguration['additionalElementPropertyPaths'][i], $(this).val());
        }
      }
    }
  });
};

/**
 * @public
 *
 * @param object editorConfiguration
 * @param object editorHtml
 * @param string collectionElementIdentifier
 * @param string collectionName
 * @return void
 * @throws 1475421053
 * @throws 1475421054
 * @throws 1475421055
 * @throws 1475421056
 */
function renderModalTextareaEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
  var propertyData, propertyPath;
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475421053
  );
  assert(
    'object' === $.type(editorHtml),
    'Invalid parameter "editorHtml"',
    1475421054
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration['label']),
    'Invalid configuration "label"',
    1475421055
  );


  getHelper()
    .getTemplatePropertyDomElement('label', editorHtml)
    .append(editorConfiguration['label']);
  if (getUtility().isNonEmptyString(editorConfiguration['fieldExplanationText'])) {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .text(editorConfiguration['fieldExplanationText']);
  } else {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .remove();
  }

  if (getUtility().isNonEmptyString(editorConfiguration['placeholder'])) {
    getHelper()
      .getTemplatePropertyDomElement('propertyPath', editorHtml)
      .attr('placeholder', editorConfiguration['placeholder']);
  }

  if (editorConfiguration['propertyPath']  !== undefined) {
    propertyPath = getFormEditorApp().buildPropertyPath(
      editorConfiguration['propertyPath'],
      collectionElementIdentifier,
      collectionName
    );
    propertyData = getCurrentlySelectedFormElement().get(propertyPath);

    //getFormEditorApp().getInspector().getEditor().getValidator()._validateCollectionElement(propertyPath, editorHtml);
    //_validateCollectionElement(propertyPath, editorHtml);

    getHelper().getTemplatePropertyDomElement('propertyPath', editorHtml).text(propertyData);
  }

  getHelper().getTemplatePropertyDomElement('stageRichtextEditor', editorHtml).on('click', function(e) {
    getPublisherSubscriber().publish(
      'view/stage/abstract/button/openMessageEditor/clicked', [
        'view/insertElements/perform/bottom'
      ]
    );
  });




  return;
  if (
    !getUtility().isUndefinedOrNull(editorConfiguration['additionalElementPropertyPaths'])
    && 'array' === $.type(editorConfiguration['additionalElementPropertyPaths'])
  ) {
    for (var i = 0, len = editorConfiguration['additionalElementPropertyPaths'].length; i < len; ++i) {
      getCurrentlySelectedFormElement().set(editorConfiguration['additionalElementPropertyPaths'][i], propertyData);
    }
  }

  renderFormElementSelectorEditorAddition(editorConfiguration, editorHtml, propertyPath);

  getHelper().getTemplatePropertyDomElement('propertyPath', editorHtml).on('keyup paste', function() {
    if (
      !!editorConfiguration['doNotSetIfPropertyValueIsEmpty']
      && !getUtility().isNonEmptyString($(this).val())
    ) {
      getCurrentlySelectedFormElement().unset(propertyPath);
    } else {
      getCurrentlySelectedFormElement().set(propertyPath, $(this).val());
    }
    _validateCollectionElement(propertyPath, editorHtml);
    if (
      !getUtility().isUndefinedOrNull(editorConfiguration['additionalElementPropertyPaths'])
      && 'array' === $.type(editorConfiguration['additionalElementPropertyPaths'])
    ) {
      for (var i = 0, len = editorConfiguration['additionalElementPropertyPaths'].length; i < len; ++i) {
        if (
          !!editorConfiguration['doNotSetIfPropertyValueIsEmpty']
          && !getUtility().isNonEmptyString($(this).val())
        ) {
          getCurrentlySelectedFormElement().unset(editorConfiguration['additionalElementPropertyPaths'][i]);
        } else {
          getCurrentlySelectedFormElement().set(editorConfiguration['additionalElementPropertyPaths'][i], $(this).val());
        }
      }
    }
  });
};
