<?php
namespace Craft;

/**
 * Class SproutEmail_CampaignModel
 *
 * @package Craft
 * --
 * @property int                      $id
 * @property string                   $name
 * @property string                   $handle
 * @property string                   $type
 * @property string                   $mailer
 * @property string                   $titleFormat
 * @property string                   $urlFormat
 * @property bool                     $hasUrls
 * @property bool                     $hasAdvancedTitles
 * @property string                   $template
 * @property string                   $templateCopyPaste
 * @property int                      $fieldLayoutId
 * @property SproutEmail_CampaignEmailModel[] $entries
 */
class SproutEmail_CampaignModel extends BaseModel
{
	public $saveAsNew;

	protected $fields;

	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'id'                => AttributeType::Number,
			'name'              => AttributeType::String,
			'handle'            => AttributeType::String,
			'type'              => array(
				AttributeType::Enum,
				'values' => array(
					Campaign::Email,
					Campaign::Notification
				)
			),
			'mailer'            => AttributeType::String,
			'titleFormat'       => AttributeType::String,
			'urlFormat'         => AttributeType::String,
			'hasUrls'           => array(
				AttributeType::Bool,
				'default' => false,
			),
			'hasAdvancedTitles' => array(
				AttributeType::Bool,
				'default' => false,
			),
			'template'          => AttributeType::String,
			'templateCopyPaste' => AttributeType::String,
			// @defaults
			'dateCreated'       => AttributeType::DateTime,
			'dateUpdated'       => AttributeType::DateTime,

			// @related
			'fieldLayoutId'     => AttributeType::Number,
			'entryId'           => AttributeType::Number
		);
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'fieldLayout' => new FieldLayoutBehavior('SproutEmail_CampaignEmail'),
		);
	}

	public function getFieldLayout()
	{
		return $this->asa('fieldLayout')->getFieldLayout();
	}

	/**
	 * Returns the fields associated with this form.
	 *
	 * @return array
	 */
	public function getFields()
	{
		if (!isset($this->fields))
		{
			$this->fields = array();

			$fieldLayoutFields = $this->getFieldLayout()->getFields();

			foreach ($fieldLayoutFields as $fieldLayoutField)
			{
				$field = $fieldLayoutField->getField();
				$field->required = $fieldLayoutField->required;
				$this->fields[] = $field;
			}
		}

		return $this->fields;
	}

	/*
	 * Sets the fields associated with this form.
	 *
	 * @param array $fields
	 */
	public function setFields($fields)
	{
		$this->fields = $fields;
	}

	/**
	 * Set the Entry Related to this Campaign if we have a notification
	 */
	public function getNotificationEmail()
	{
		if ($this->isNotification())
		{
			return sproutEmail()->notificationEmails->getNotificationEmailByCampaignId($this->id);
		}
	}

	/**
	 * @return bool
	 */
	public function isNotification()
	{
		return ($this->type == Campaign::Notification);
	}
}
