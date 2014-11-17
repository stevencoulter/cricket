<?php

/*
 * (C) Copyright 2014 Steven Coulter <steven.coulter@gmail.com>
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

abstract class Grid extends Table {

	public function __construct($inID, Table_Sort $inDefaultSort, $inPageSize, $inCols, $inAdjacentPages = 3){
		$this->numColumns = $inCols;
		parent::__construct($inID,$inDefaultSort,$inPageSize,$inAdjacentPages);
	}
	
	protected function renderTable($inParams) {
		$inParams['numColumns'] = $this->numColumns;
		$this->renderGrid($inParams);
	}
	
	public function action_sort() {
		$column = $this->getParameter('column');
		$direction = $this->getParameter('direction');
	
		$this->sort = new Table_Sort($column, $direction);
	
		$this->setStart(0);
	
		$this->invalidate();
	}
	
	abstract protected function renderGrid($inParams);
}