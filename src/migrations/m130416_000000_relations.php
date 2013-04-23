<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130416_000000_relations extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Add a new fieldId column to the links table
		$this->addColumnAfter('links', 'fieldId', array('column' => ColumnType::Int), 'criteriaId');

		// Find all the former Links fields
		$fields = craft()->db->createCommand()
			->select('id,settings')
			->from('fields')
			->where(array('type' => 'Links'))
			->queryAll();

		$elementTypes = array(
			'Entry' => array('sourcePrefix' => 'section', 'fieldtypeClass' => 'Entries'),
			'User'  => array('sourcePrefix' => 'group',   'fieldtypeClass' => 'Users'),
			'Asset' => array('sourcePrefix' => 'source',  'fieldtypeClass' => 'Assets'),
		);

		foreach ($fields as $field)
		{
			$fieldSettings = JsonHelper::decode($field['settings']);

			// Default to 'Entry', since that's what the LinksFieldType did
			$elementType = 'Entry';

			// Does it have a criteria?
			if (isset($fieldSettings['criteriaId']))
			{
				$criteriaId = $fieldSettings['criteriaId'];

				$criteria = craft()->db->createCommand()
					->select('rightElementType,rightSettings')
					->from('linkcriteria')
					->where(array('id' => $criteriaId))
					->queryRow();

				if ($criteria)
				{
					$criteriaSettings = JsonHelper::decode($criteria['rightSettings']);

					// Set to a valid element type?
					if ($criteria['rightElementType'] && isset($elementTypes[$criteria['rightElementType']]))
					{
						$elementType = $criteria['rightElementType'];
					}

					// Migrate the sources into the field's settings
					$fieldSettings['sources'] = array();

					$sourcePrefix = $elementTypes[$elementType]['sourcePrefix'];

					if (!empty($criteriaSettings[$sourcePrefix.'Id']))
					{
						foreach ($criteriaSettings[$sourcePrefix.'Id'] as $sourceId)
						{
							$fieldSettings['sources'][] = $sourcePrefix.':'.$sourceId;
						}
					}

					// Update the links with the field ID
					$this->update('links', array('fieldId' => $field['id']), array('criteriaId' => $criteriaId));
				}
			}

			// Update the field
			unset($fieldSettings['criteriaId']);
			unset($fieldSettings['addLabel']);
			unset($fieldSettings['removeLabel']);

			$this->update('fields', array(
				'type'     => $elementTypes[$elementType]['fieldtypeClass'],
				'settings' => JsonHelper::encode($fieldSettings)
			), array(
				'id' => $field['id']
			));
		}


		// Drop any links that didn't get a fieldId
		$this->delete('links', '`fieldId` IS NULL');

		// Temporarily drop the FK's and indexes on the links table
		$this->dropForeignKey('links', 'criteriaId');
		$this->dropForeignKey('links', 'leftElementId');
		$this->dropForeignKey('links', 'rightElementId');
		$this->dropIndex('links', 'criteriaId,leftElementId,rightElementId', true);

		// Update the columns
		$this->dropColumn('links', 'criteriaId');
		$this->alterColumn('links', 'fieldId', array('column' => ColumnType::Int, 'null' => false));
		$this->renameColumn('links', 'leftElementId', 'parentId');
		$this->renameColumn('links', 'rightElementId', 'childId');
		$this->dropColumn('links', 'leftSortOrder');
		$this->renameColumn('links', 'rightSortOrder', 'sortOrder');

		// Rename the table!
		$this->renameTable('links', 'relations');

		// Add the indexes and foreign keys back
		$this->createIndex('relations', 'fieldId,parentId,childId', true);
		$this->addForeignKey('relations', 'fieldId', 'fields', 'id');
		$this->addForeignKey('relations', 'parentId', 'elements', 'id');
		$this->addForeignKey('relations', 'childId', 'elements', 'id');

		// Drop the linkcriteria table
		$this->dropTable('linkcriteria');

		return true;
	}
}
