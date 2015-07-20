<?php
namespace Craft;

class SproutEmail_EntryElementType extends BaseElementType
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Sprout Email Entry');
	}

	/**
	 * @return bool
	 */
	public function hasTitles()
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function isLocalized()
	{
		return false;
	}

	/**
	 * @inheritDoc IElementType::hasStatuses()
	 *
	 * @return bool
	 */
	public function hasStatuses()
	{
		return true;
	}

	public function getStatuses()
	{
		return array(
			SproutEmail_EntryModel::READY    => Craft::t('Ready'),
			SproutEmail_EntryModel::PENDING  => Craft::t('Pending'),
			SproutEmail_EntryModel::DISABLED => Craft::t('Disabled'),
			SproutEmail_EntryModel::ARCHIVED => Craft::t('Archived'),
		);
	}

	/**
	 * Returns this element type's sources.
	 *
	 * @param string|null $context
	 *
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		// Grab all of our Notifications
		$notifications   = sproutEmail()->campaigns->getCampaigns('notification');
		$notificationIds = array();

		$sources = array(
			'*' => array(
				'label' => Craft::t('All Emails'),
			),
		);

		if (count($notifications))
		{
			// Create a list of Notification IDs we can use as criteria to filter by
			foreach ($notifications as $notification)
			{
				$notificationIds[] = $notification->id;
			}

			$sources['notifications'] = array(
				'label'    => Craft::t('Notifications'),
				'criteria' => array(
					'campaignId' => $notificationIds
				)
			);
		}

		// Prepare the data for our sources sidebar
		$campaigns = sproutEmail()->campaigns->getCampaigns('email');

		if (count($campaigns))
		{
			$sources[] = array('heading' => Craft::t('Campaigns'));

			foreach ($campaigns as $campaign)
			{
				$key = 'campaign:'.$campaign->id;

				$sources[$key] = array(
					'label'    => $campaign->name,
					'data'     => array('campaignId' => $campaign->id),
					'criteria' => array('campaignId' => $campaign->id)
				);
			}
		}

		return $sources;
	}

	/**
	 * Returns the attributes that can be shown/sorted by in table views.
	 *
	 * @param string|null $source
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		return array(
			'title' => Craft::t('Subject Line'),
			'dateCreated' => Craft::t('Date Created'),
			'type' => Craft::t('Type'),
			'preview' => Craft::t('Preview'),
			'review' => null,
		);
	}

	/**
	 * @param BaseElementModel $element
	 * @param string           $attribute
	 *
	 * @return string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
		$campaign = sproutEmail()->campaigns->getCampaignById($element->campaignId);
		$mailer = sproutEmail()->mailers->getMailerByName($campaign->mailer);
		switch ($attribute)
		{
			case 'preview':
				$link = null;
				$textLabel = Craft::t('Text');
				if($mailer != null &&  $mailer->isInstalled() && !$campaign->isNotification() && $element->status == SproutEmail_EntryModel::READY)
				{
					$link = "<a href='{$element->url}' title='{$element->title}' target='_blank'>HTML</a>
					&nbsp;/&nbsp;
					<a href='{$element->url}?type=text' title='{$element->subjectLine}' target='_blank'>{$textLabel}</a>";
				}

				return $link;

			case 'review':
				$link = null;

				if($mailer != null && $mailer->isInstalled() && $element->status == SproutEmail_EntryModel::READY)
				{
					$link = "<a href='#'
						data-action='{$mailer->getActionForPrepareModal()}'
						data-mailer='{$mailer->getId()}'
						data-entry-id='{$element->id}'
						data-campaign-id='{$campaign->id}'
						class='prepare'>{$mailer->getPrepareModalLinkTitle()}</a>";
				}

				return $link;

			case 'type':

				return ucfirst($element->type);

			default:
				return parent::getTableAttributeHtml($element, $attribute);
				break;
		}
	}

	/**
	 * @inheritDoc IElementType::getIndexHtml()
	 *
	 * @param ElementCriteriaModel $criteria
	 * @param array                $disabledElementIds
	 * @param array                $viewState
	 * @param null|string          $sourceKey
	 * @param null|string          $context
	 * @param bool                 $includeContainer
	 * @param bool                 $showCheckboxes
	 *
	 * @return string
	 */
	public function getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes)
	{
		$variables = array(
			'viewMode'            => $viewState['mode'],
			'context'             => $context,
			'elementType'         => new ElementTypeVariable($this),
			'disabledElementIds'  => $disabledElementIds,
			'collapsedElementIds' => craft()->request->getParam('collapsedElementIds'),
			'showCheckboxes'      => $showCheckboxes,
		);
		//SproutEmail resources
		craft()->templates->includeJsResource('sproutemail/js/sproutmodal.js');
		craft()->templates->includeJs('var sproutModalInstance = new SproutModal(); sproutModalInstance.init();');
		sproutEmail()->mailers->includeMailerModalResources();

		// Special case for sorting by structure
		if (isset($viewState['order']) && $viewState['order'] == 'structure')
		{
			$source = $this->getSource($sourceKey, $context);

			if (isset($source['structureId']))
			{
				$criteria->order = 'lft asc';
				$variables['structure'] = craft()->structures->getStructureById($source['structureId']);

				// Are they allowed to make changes to this structure?
				if ($context == 'index' && $variables['structure'] && !empty($source['structureEditable']))
				{
					$variables['structureEditable'] = true;

					// Let StructuresController know that this user can make changes to the structure
					craft()->userSession->authorize('editStructure:'.$variables['structure']->id);
				}
			}
			else
			{
				unset($viewState['order']);
			}
		}
		else if (!empty($viewState['order']) && $viewState['order'] == 'score')
		{
			$criteria->order = 'score';
		}
		else
		{
			$sortableAttributes = $this->defineSortableAttributes();

			if ($sortableAttributes)
			{
				$order = (!empty($viewState['order']) && isset($sortableAttributes[$viewState['order']])) ? $viewState['order'] : array_shift(array_keys($sortableAttributes));
				$sort  = (!empty($viewState['sort']) && in_array($viewState['sort'], array('asc', 'desc'))) ? $viewState['sort'] : 'asc';
				$criteria->order = preg_replace('/^(.*?)(?:\s+(?:asc|desc))?(,.*)?$/i', "$1 {$sort}$2", $order);
			}
		}

		switch ($viewState['mode'])
		{
			case 'table':
			{
				// Get the table columns
				$variables['attributes'] = $this->defineTableAttributes($sourceKey);

				break;
			}
		}

		$variables['elements'] = $criteria->find();

		$template = '_elements/'.$viewState['mode'].'view/'.($includeContainer ? 'container' : 'elements');
		return craft()->templates->render($template, $variables);
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'title'          => AttributeType::String,
			'campaignId'     => AttributeType::Number,
			'campaignHandle' => AttributeType::Handle,
		);
	}

	/**
	 * @inheritDoc IElementType::getElementQueryStatusCondition()
	 *
	 * @param DbCommand $query
	 * @param string    $status
	 *
	 * @return array|false|string|void
	 */
	public function getElementQueryStatusCondition(DbCommand $query, $status)
	{
		switch ($status)
		{
			case SproutEmail_EntryModel::DISABLED:
			{
				$query->andWhere('elements.enabled = 0');

				break;
			}
			case SproutEmail_EntryModel::PENDING:
			{
				$query->andWhere('campaigns.template IS NULL OR campaigns.mailer IS NULL');

				break;
			}
			case SproutEmail_EntryModel::ARCHIVED:
			{
				$query->andWhere('entries.sent > 0');
				$query->orWhere('elements.archived = 1');
				break;
			}
			case SproutEmail_EntryModel::READY:
			{
				$query->andWhere('
					elements.enabled = 1
					AND campaigns.template IS NOT NULL
					AND campaigns.mailer IS NOT NULL
					AND entries.sent = 0'
				);

				break;
			}
		}
	}

	/**
	 * Defines which model attributes should be searchable.
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		return array('title');
	}

	/**
	 * Modifies an element query targeting elements of this type.
	 *
	 * @param DbCommand            $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect(
				'entries.id
				, entries.subjectLine as subjectLine
				, entries.campaignId as campaignId
				, entries.recipients as recipients
				, entries.fromName as fromName
				, entries.fromEmail as fromEmail
				, entries.replyTo as replyTo
				, entries.sent as sent
				, campaigns.type as type'
			)
			->join('sproutemail_campaigns_entries entries', 'entries.id = elements.id')
			->join('sproutemail_campaigns campaigns', 'campaigns.id = entries.campaignId');

		if ($criteria->campaignId)
		{
			$query->andWhere(DbHelper::parseParam('entries.campaignId', $criteria->campaignId, $query->params));
		}

		if ($criteria->campaignHandle)
		{
			$query->andWhere(DbHelper::parseParam('campaigns.handle', $criteria->campaignHandle, $query->params));
		}
	}

	/**
	 * Gives us the ability to render campaign previews by using the Craft API and templates/render
	 *
	 * @param BaseElementModel $element
	 *
	 * @return array|bool|mixed
	 */
	public function routeRequestForMatchedElement(BaseElementModel $element)
	{
		$campaign = sproutEmail()->campaigns->getCampaignById($element->campaignId);

		if (!$campaign)
		{
			return false;
		}

		$extension = null;

		if (($type = craft()->request->getQuery('type')))
		{
			$extension = in_array(strtolower($type), array('txt', 'text')) ? '.txt' : null;
		}

		if (!craft()->templates->doesTemplateExist($campaign->template.$extension))
		{
			$templateName = $campaign->template.$extension;
			sproutEmail()->error(Craft::t("The template '{templateName}' could not be found", array(
				'templateName' => $templateName
			)));
		}

		$vars = array(
			'entry'     => $element,
			'campaign'  => $campaign,
			'recipient' => array(
				'firstName' => '{firstName}',
				'lastName'  => '{lastName}',
				'email'     => '{email}'
			),
			'firstName' => '{firstName}',
			'lastName'  => '{lastName}',
			'email'     => '{email}'
		);

		return array(
			'action' => 'templates/render',
			'params' => array(
				'template'  => $campaign->template.$extension,
				'variables' => $vars
			)
		);
	}

	/**
	 * @inheritDoc IElementType::getAvailableActions()
	 *
	 * @param string|null $source
	 *
	 * @return array|null
	 */
	public function getAvailableActions($source = null)
	{
		$deleteAction = craft()->elements->getAction('Delete');

		$deleteAction->setParams(
			array(
				'confirmationMessage' => Craft::t('Are you sure you want to delete the selected emails?'),
				'successMessage'      => Craft::t('Emails deleted.'),
			)
		);

		$setStatusAction = craft()->elements->getAction('SproutEmail_SetStatus');

		return array($deleteAction, $setStatusAction);
	}

	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return SproutEmail_EntryModel::populateModel($row);
	}
}
