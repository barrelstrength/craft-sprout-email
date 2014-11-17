<?php
namespace Craft;

/**
 * Campaign record
 */
class SproutEmail_CampaignRecord extends BaseRecord
{
	public $rules = array ();
	public $sectionRecord;
	
	/**
	 * Return table name corresponding to this record
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_campaigns';
	}
	
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return multitype:multitype:string multitype:boolean string
	 */
	public function defineAttributes()
	{
		// @TODO - where do we load this smarter? Already in init()
		// but needed here for install.
		Craft::import('plugins.sproutemail.enums.Campaign');

		return array (
			'fieldLayoutId'    => AttributeType::Number,
			'emailProvider'    => AttributeType::String,
			'name'             => AttributeType::String,
			'handle'           => AttributeType::String,
			'type'             => array(
														AttributeType::Enum, 
														'values' => array(
															Campaign::Email, 
															Campaign::Notification
														)),
			'titleFormat'      => AttributeType::String,
			'hasUrls'          => array(
															AttributeType::Bool, 
															'default' => true,
														),
			'hasAdvancedTitles' => array(
															AttributeType::Bool, 
															'default' => true,
														 ),
			'subject'          => AttributeType::String,
			'fromName'         => AttributeType::String,
			'fromEmail'        => AttributeType::Email,
			'replyToEmail'     => AttributeType::Email,

			'urlFormat'        => AttributeType::String,
			'template'         => AttributeType::String,
			'templateCopyPaste'=> AttributeType::String,
			
			'recipients'       => AttributeType::Mixed,
			'dateCreated'      => AttributeType::DateTime,
			'dateUpdated'      => AttributeType::DateTime 
		);
	}
	
	/**
	 * Record relationships
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array (
			'fieldLayout' => array(
				static::BELONGS_TO, 
				'FieldLayoutRecord', 
				'onDelete' => static::SET_NULL
			),
			'campaignRecipientList' => array (
				self::HAS_MANY,
				'SproutEmail_CampaignRecipientListRecord',
				'campaignId' 
			),
			'recipientList' => array (
				self::HAS_MANY,
				'SproutEmail_RecipientListRecord',
				'recipientListId',
				'through' => 'campaignRecipientList' 
			),
			'campaignNotificationEvent' => array (
				self::HAS_MANY,
				'SproutEmail_CampaignNotificationEventRecord',
				'campaignId' 
			),
			'notificationEvent' => array (
				self::HAS_MANY,
				'SproutEmail_NotificationEventRecord',
				'notificationEventId',
				'through' => 'campaignNotificationEvent' 
			) 
		);
	}
	
	/**
	 * Function for adding rules
	 *
	 * @param array $rules            
	 * @return void
	 */
	public function addRules($rules = array())
	{
		$this->rules [] = $rules;
	}
	
	/**
	 * Yii style validation rules;
	 * These are the 'base' rules but specific ones are added in the service based on
	 * the scenario
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = array (
			// required fields
			array (
				'name',
				'required' 
			),
			// must be valid emails
			array (
				'fromEmail',
				'email' 
			),
			// custom
			array (
				'emailProvider',
				'validEmailProvider' 
			)
		);
		
		return array_merge( $rules, $this->rules );
	}
	
	/**
	 * Custom email provider validator
	 *
	 * @param string $attr            
	 * @param array $params            
	 * @return void
	 */
	public function validEmailProvider($attr, $params)
	{
		if ( $this->{$attr} != 'SproutEmail' && ! array_key_exists( $this->{$attr}, craft()->sproutEmail_emailProvider->getEmailProviders() ) )
		{
			$this->addError( $attr, 'Invalid email provider.' );
		}
	}
}