<?php
namespace Craft;

/**
 * Email Blast Type controller
 */
class SproutEmail_EmailBlastTypeController extends BaseController
{
	
	/**
	 * Export emailBlastType
	 *
	 * @return void
	 */
	public function actionExport()
	{
		$emailBlastType = craft()->sproutEmail->getEmailBlast( array (
				'id' => craft()->request->getPost( 'emailBlastTypeId' ) 
		) );
		
		if ( $emailBlastType->emailProvider != 'SproutEmail' )
		{
			craft()->sproutEmail_emailProvider->exportEmailBlast( craft()->request->getPost( 'entryId' ), craft()->request->getPost( 'emailBlastTypeId' ) );
		}
		else
		{
			craft()->sproutEmail_emailProvider->exportEmailBlast( craft()->request->getPost( 'entryId' ), craft()->request->getPost( 'emailBlastTypeId' ) );
						
			craft()->tasks->createTask( 'SproutEmail_RunEmailBlastType', Craft::t( 'Running emailBlastType' ), array (
					'emailBlastTypeId' => craft()->request->getPost( 'emailBlastTypeId' ),
					'entryId' => craft()->request->getPost( 'entryId' ) 
			) );

			// Apparently not. Is there a pending task?
			$task = craft()->tasks->getNextPendingTask();
			
			if ( $task )
			{
				// Return info about the next pending task without stopping PHP execution
				JsonHelper::sendJsonHeaders();
				craft()->request->close( JsonHelper::encode( 'EmailBlastType successfully scheduled.' ) );
				
				// Start running tasks
				craft()->tasks->runPendingTasks();
			}
		}
	}
	
	/**
	 * Save emailBlastType
	 *
	 * @return void
	 */
	public function actionSaveEmailBlastType()
	{
		$this->requirePostRequest();
		
		$emailBlastTypeId = craft()->request->getRequiredPost('sproutEmail.id');

		// @TODO - clean this up
		$emailBlastType = craft()->sproutEmail_emailBlastType->getEmailBlastTypeById($emailBlastTypeId);
		$emailBlastType->setAttributes( craft()->request->getPost('sproutEmail') );

		$useRecipientLists = craft()->request->getPost( 'useRecipientLists' ) ? 1 : 0;
		$emailBlastType->useRecipientLists = $useRecipientLists;

		if (craft()->request->getPost('fieldLayout')) 
		{
			// Set the field layout
			$fieldLayout =  craft()->fields->assembleLayoutFromPost();
							
			$fieldLayout->type = 'SproutEmail_EmailBlastType';
			$emailBlastType->setFieldLayout($fieldLayout);
		}

		$tab = craft()->request->getPost( 'tab' );

		if ( $emailBlastTypeId = craft()->sproutEmail_emailBlastType->saveEmailBlastType( $emailBlastType,  $tab) )
		{
			// if this was called by the child (Notifications), return the model
			// if ( get_class( $this ) == 'Craft\SproutEmail_NotificationsController' )
			// {
			// 	$emailBlastType->id = $emailBlastTypeId;
			// 	return $emailBlastType;
			// }

			$_POST['redirect'] = str_replace('{id}', $emailBlastType->id, $_POST['redirect']);

			craft()->userSession->setNotice( Craft::t( 'Email Blast Type successfully saved.' ) );

			$continue = craft()->request->getPost( 'continue' );
			
			if ($continue == 'info')
			{
				if(craft()->request->getPost( 'emailProvider' ) == 'CopyPaste')
				{
					$this->redirect( 'sproutemail/settings/emailblasttypes/edit/' . $emailBlastTypeId . '/template' );
				}
				else
				{
					$this->redirect( 'sproutemail/settings/emailblasttypes/edit/' . $emailBlastTypeId . '/recipients' );
				}
			}
			elseif($continue == 'recipients')
			{
				$this->redirect( 'sproutemail/settings/emailblasttypes/edit/' . $emailBlastTypeId . '/template' );
			}
			else
			{
				$this->redirectToPostedUrl($emailBlastType);
			}
		}
		else
		{
			SproutEmailPlugin::log(json_encode($emailBlastType->getErrors()));
			
			craft()->userSession->setError( Craft::t( 'Please correct the errors below.' ) );
			
			// if this was called by the child (Notifications), return the model
			// if ( get_class( $this ) == 'Craft\SproutEmail_NotificationsController' )
			// {
			// 	return $emailBlastType;
			// }

			// Send the field back to the template
			craft()->urlManager->setRouteVariables(array(
				'emailBlastType' => $emailBlastType 
			));
		}
	}
	
	/**
	 * Delete emailBlastType
	 *
	 * @return void
	 */
	public function actionDeleteEmailBlastType()
	{
		$this->requirePostRequest();
		
		$emailBlastTypeId = craft()->request->getRequiredPost('id');

		// @TODO - handle errors
		if (craft()->sproutEmail_emailBlastType->deleteEmailBlastType($emailBlastTypeId)) 
		{
			craft()->userSession->setNotice(Craft::t('Email Blast Type deleted.'));

			$this->redirectToPostedUrl();	
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t delete Email Blast Type.'));
		}
	}
	
	/**
	 * Save settings
	 *
	 * @return void
	 */
	public function actionSaveSettings()
	{
		$this->requirePostRequest();
		
		foreach ( craft()->request->getPost( 'settings' ) as $provider => $settings )
		{
			$service = 'sproutEmail_' . lcfirst( $provider );
			craft()->$service->saveSettings( $settings );
		}
		
		craft()->userSession->setNotice( Craft::t( 'Settings successfully saved.' ) );
		$this->redirectToPostedUrl();
	}


	public function actionEmailBlastTypeSettingsTemplate(array $variables = array())
	{
		if (isset($variables['emailBlastTypeId'])) 
		{
			// If emailBlastType already exists, we're returning an error object
			if ( ! isset($variables['emailBlastType']) ) 
			{
				$variables['emailBlastType'] = craft()->sproutEmail_emailBlastType->getEmailBlastTypeById($variables['emailBlastTypeId']);
			}
		}
		else
		{	
			$variables['emailBlastType'] = new SproutEmail_EmailBlastType();
		}
		
		// Load our template
		$this->renderTemplate('sproutemail/settings/emailblasttypes/_edit', $variables);
	}
}