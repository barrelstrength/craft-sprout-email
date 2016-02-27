<?php
namespace Craft;

class SproutEmailController extends BaseController
{
	/**
	 * @throws HttpException
	 */
	public function actionSettingsIndexTemplate()
	{
		if (!sproutEmail()->checkPermission())
		{
			$this->redirect('sproutemail');
		}

		if (!craft()->request->getSegment(3))
		{
			$this->redirect('sproutemail/settings/general');
		}

		$variables['settingsTemplate'] = craft()->request->getSegment(3);

		$variables['settings'] = craft()->plugins->getPlugin('sproutemail')->getSettings();

		$this->renderTemplate('sproutemail/settings/_tabs/' . $variables['settingsTemplate'], $variables);
	}

	/**
	 * @return void
	 */
	public function actionSavePluginSettings()
	{
		$this->requirePostRequest();

		$plugin = craft()->plugins->getPlugin('sproutemail');
		$settings = craft()->request->getPost('settings');

		if (craft()->plugins->savePluginSettings($plugin, $settings))
		{
			craft()->userSession->setNotice(Craft::t('Settings saved.'));

			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Could not save settings.'));

			craft()->urlManager->setRouteVariables(
				array(
					'settings' => $settings
				)
			);
		}
	}

	public function actionSandbox()
	{
		$record = SproutEmail_CoreMailerRecipientListRecord::model()->findById(1);

		if ($record)
		{
			Craft::dump($record->getAttributes());

			$recipients = $record->recipients();

			echo '<hr>';
			Craft::dump(count($recipients));
			if ($recipients)
			{
				foreach ($recipients as $recipient)
				{
					echo '<hr>';
					Craft::dump($recipient->getAttributes());
					echo '<hr>';
					Craft::dump($recipient->groups());
				}
			}
		}

		exit;
	}
}
