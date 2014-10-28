<?php

/*
 * (C) Copyright 2014 Bill Hubauer <bill@hubauer.com>
 * 
 * This file is part of Cricket  https://github.com/bhubauer/cricket
 * 
 * This library is free software; you can redistribute it and/or modify it under the terms of the 
 * GNU Lesser General Public License as published by the Free Software Foundation; either 
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License along with this library; 
 * if not, visit http://www.gnu.org/licenses/lgpl-2.1.html or write to the 
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */


namespace cricket\components;

/**
 * A class to hold a collection of keys and orders from which to sort a table.
 * 
 * This class contains methods callable from tables getDataSet method to do the sorting for you -- sql, propel, array key, array value
 *
 */

class Table_Sort {
    public $desc;
    public $column;
    
    public function __construct($inCol,$inDesc) {
        $this->desc = $inDesc;
        $this->column = $inCol;
		$this->nextSort = null;
    }
    
    public function setSort($inCol, $inMultiSort = false) {
    	$sortSet = false;
    	if ($inMultiSort) {
    		$currentSort = $this;
			while ($currentSort) {
				$lastSort = $currentSort;
				if ($inCol == $currentSort->column) {
					$currentSort->desc = !$currentSort->desc;
					$sortSet = true;					
					break;
				}
				$currentSort = $currentSort->nextSort;
			}
			if (!$sortSet)	{
    			$lastSort->nextSort = new Table_Sort($inCol, false);
			}	
		} else {
			$this->nextSort = null;
	        if($inCol == $this->column) {
	            $this->desc = !$this->desc;
	        }else{
	            $this->column = $inCol;
	            $this->desc = false;
	        }
		}
    }
    
    public function getOrderBy() {
    	$currentSort = $this;
		$sorts = array();
		while ($currentSort) {
        	$sorts[] = '`' . $currentSort->column . '`' . " " . ($currentSort->desc ? 'desc' : 'asc');
			$currentSort = $currentSort->nextSort;
		}
		return join(",",$sorts);
    }
	
	public function getPropelOrderBy(&$collection) {
		$currentSort = $this;
		while ($currentSort) {
			$currentCollection = $collection;
			$relatedCollections = array();
			$relations = explode("::", $currentSort->column);
			$fieldName = array_pop($relations);
			
			foreach ($relations as $relation) {
				$r = "use{$relation}Query";
				$relatedCollection = $currentCollection->$r();
				$relatedCollections[] = $relatedCollection;
				$currentCollection = $relatedCollection;				
			}
			
			$s = "orderBy".$fieldName;
			$direction = $currentSort->desc ? 'desc' : 'asc';
			
			$currentCollection->$s($direction);
			
			while($relatedCollections) {
				$relatedCollection = array_pop($relatedCollections);
				$relatedCollection->endUse();
			}
			
			$currentSort = $currentSort->nextSort;
		}
	}

	public function getObjectMethodOrderBy(&$collection) {
		$currentSort = $this;
		$sorts = array();
		
		while ($currentSort) {
			$sorts[$currentSort->column] = $currentSort->desc;
			$currentSort = $currentSort->nextSort;
		}
		
		usort($collection, function($a, $b) use ($sorts) {
			$results = array();
			foreach ($sorts as $sort=>$desc) {
				$a_ans = $a->$sort();
				$b_ans = $b->$sort();
				if ($a_ans > $b_ans) {
					$val = 1;
				} else if ($a_ans < $b_ans) {
					$val = -1;
				} else {
					$val = 0;
				}
				$val *= ($desc) ? -1 : 1;
				$results[] = $val;
			}
			
			foreach ($results as $result)
				if ($result)
					return $result;
			return 0;
		});
	}
	
	public function getObjectPropertyOrderBy(&$collection) {
		$currentSort = $this;
		$sorts = array();
	
		while ($currentSort) {
			$sorts[$currentSort->column] = $currentSort->desc;
			$currentSort = $currentSort->nextSort;
		}
	
		usort($collection, function($a, $b) use ($sorts) {
			$results = array();
			foreach ($sorts as $sort=>$desc) {
				$a_ans = $a->$sort;
				$b_ans = $b->$sort;
				if ($a_ans > $b_ans) {
					$val = 1;
				} else if ($a_ans < $b_ans) {
					$val = -1;
				} else {
					$val = 0;
				}
				$val *= ($desc) ? -1 : 1;
				$results[] = $val;
			}
				
			foreach ($results as $result)
			if ($result)
				return $result;
			return 0;
		});
	}
	
	public function getArrayValueOrderBy(&$collection) {
		$currentSort = $this;
		$sorts = array();
	
		while ($currentSort) {
			$sorts[$currentSort->column] = $currentSort->desc;
			$currentSort = $currentSort->nextSort;
		}
	
		usort($collection, function($a, $b) use ($sorts) {
			$results = array();
			foreach ($sorts as $sort=>$desc) {
				$a_ans = $a[$sort];
				$b_ans = $b[$sort];
				if ($a_ans > $b_ans) {
					$val = 1;
				} else if ($a_ans < $b_ans) {
					$val = -1;
				} else {
					$val = 0;
				}
				$val *= ($desc) ? -1 : 1;
				$results[] = $val;
			}
	
			foreach ($results as $result)
			if ($result)
				return $result;
			return 0;
		});
	}
	
	
    public function equalTo(Table_Sort $inSort) {
        if ($inSort->column == $this->column) {
            if ($inSort->desc == $this->desc) {
                return true;
            }
        }
    }
}

