<?php
namespace Craft;

/**
 * Recipient list record
 */
class SproutEmail_RecipientListRecord extends BaseRecord
{
	/**
	 * Return table name corresponding to this record
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_recipientlists';
	}
	
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array (
			'emailProviderRecipientListId' => AttributeType::String,
			'emailProvider'                => AttributeType::String,
			'type'                         => AttributeType::String,
			'dateCreated'                  => AttributeType::DateTime,
			'dateUpdated'                  => AttributeType::DateTime 
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
			'emailBlastTypeRecipientList' => array (
				self::HAS_MANY,
				'SproutEmail_EmailBlastTypeRecipientListRecord',
				'recipientListId' 
			),
			'emailBlastType' => array (
				self::HAS_MANY,
				'SproutEmail_EmailBlastTypeRecord',
				'emailBlastTypeId',
				'through' => 'emailBlastTypeRecipientList' 
			) 
		);
	}
}
