<?php
/**
 * Element: VirtueMart
 *
 * @package         NoNumber Framework
 * @version         15.2.11
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2015 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_PLUGINS . '/system/nnframework/helpers/groupfield.php';

class JFormFieldNN_VirtueMart extends nnFormGroupField
{
	public $type = 'VirtueMart';

	protected function getInput()
	{
		if ($error = $this->missingFilesOrTables(array('categories', 'products')))
		{
			return $error;
		}

		return $this->getSelectList();
	}

	function getCategories()
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from('#__virtuemart_categories AS c')
			->where('c.published > -1');
		$this->db->setQuery($query);
		$total = $this->db->loadResult();

		if ($total > $this->max_list_count)
		{
			return -1;
		}

		$query->clear()
			->select('c.virtuemart_category_id as id, cc.category_parent_id AS parent_id, l.category_name AS title, c.published')
			->from('#__virtuemart_categories_' . $this->getActiveLanguage() . ' AS l')
			->join('', '#__virtuemart_categories AS c using (virtuemart_category_id)')
			->join('LEFT', '#__virtuemart_category_categories AS cc ON l.virtuemart_category_id = cc.category_child_id')
			->where('c.published > -1')
			->order('c.ordering, l.category_name');
		$this->db->setQuery($query);
		$items = $this->db->loadObjectList();

		return $this->getOptionsTreeByList($items);
	}

	function getProducts()
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from('#__virtuemart_products AS p')
			->where('p.published > -1');
		$this->db->setQuery($query);
		$total = $this->db->loadResult();

		if ($total > $this->max_list_count)
		{
			return -1;
		}

		$lang = $this->getActiveLanguage();

		$query->clear()
			->select('p.virtuemart_product_id as id, l.product_name AS name, p.product_sku as sku, cl.category_name AS cat, p.published')
			->from('#__virtuemart_products AS p')
			->join('LEFT', '#__virtuemart_products_' . $lang . ' AS l ON l.virtuemart_product_id = p.virtuemart_product_id')
			->join('LEFT', '#__virtuemart_product_categories AS x ON x.virtuemart_product_id = p.virtuemart_product_id')
			->join('LEFT', '#__virtuemart_categories AS c ON c.virtuemart_category_id = x.virtuemart_category_id')
			->join('LEFT', '#__virtuemart_categories_' . $lang . ' AS cl ON cl.virtuemart_category_id = c.virtuemart_category_id')
			->where('p.published > -1')
			->order('l.product_name, p.product_sku');
		$this->db->setQuery($query);
		$list = $this->db->loadObjectList();

		return $this->getOptionsByList($list, array('sku', 'cat'));
	}

	private function getActiveLanguage()
	{
		$query = $this->db->getQuery(true)
			->select('config')
			->from('#__virtuemart_configs')
			->where('virtuemart_config_id = 1');
		$this->db->setQuery($query);
		$config = $this->db->loadResult();

		switch (true)
		{
			case (strpos($config, 'active_languages=') !== false):
				$lang = substr($config, strpos($config, 'active_languages='));
				if (strpos($lang, '|=') !== false)
				{
					$lang = substr($lang, 0, strpos($lang, '|'));
				}
				$lang = explode('=', $lang);
				$lang = unserialize($lang[1]);

				if (isset($lang[0]))
				{
					$lang = strtolower($lang[0]);
					$lang = str_replace('-', '_', $lang);

					return $lang;
				}

			case (strpos($config, 'vmlang=') !== false) :
				$lang = substr($config, strpos($config, 'vmlang='));
				if (strpos($lang, '|=') !== false)
				{
					$lang = substr($lang, 0, strpos($lang, '|'));
				}

				if (preg_match('#"([^"]*_[^"]*)"#', $lang, $lang))
				{
					return $lang['1'];
				}

			default:
				return 'en_gb';
		}
	}
}
