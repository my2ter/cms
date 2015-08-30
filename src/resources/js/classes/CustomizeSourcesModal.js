/**
 * Customize Sources modal
 */
Craft.CustomizeSourcesModal = Garnish.Modal.extend(
{
	elementIndex: null,

	$sidebar: null,
	$sourcesContainer: null,
	$sourceSettingsContainer: null,
	$newHeadingBtn: null,
	$footer: null,
	$footerBtnContainer: null,
	$saveBtn: null,
	$cancelBtn: null,
	$saveSpinner: null,
	$loadingSpinner: null,

	sourceSort: null,
	sources: null,
	selectedSource: null,

	availableTableAttributes: null,

	init: function(elementIndex)
	{
		this.base();
		this.elementIndex = elementIndex;

		var $container = $('<form class="modal customize-sources-modal"/>').appendTo(Garnish.$bod);
		this.setContainer($container);

		this.$sidebar = $('<div class="cs-sidebar block-types"/>').appendTo(this.$container);
		this.$sourcesContainer = $('<div class="sources">').appendTo(this.$sidebar);
		this.$sourceSettingsContainer = $('<div class="source-settings">').appendTo(this.$container);

		this.$footer = $('<div class="footer"/>').appendTo($container);
		this.$footerBtnContainer = $('<div class="buttons right"/>').appendTo(this.$footer);
		this.$cancelBtn = $('<div class="btn" role="button"/>').text(Craft.t('Cancel')).appendTo(this.$footerBtnContainer);
		this.$saveBtn = $('<div class="btn submit disabled" role="button"/>').text(Craft.t('Save')).appendTo(this.$footerBtnContainer);
		this.$saveSpinner = $('<div class="spinner hidden"/>').appendTo(this.$footerBtnContainer);
		this.$newHeadingBtn = $('<div class="btn add icon"/>').text(Craft.t('New heading')).appendTo($('<div class="buttons left"/>').appendTo(this.$footer));

		this.$loadingSpinner = $('<div class="spinner"/>').appendTo(this.$container);

		this.show();

		var data = {
			elementType: this.elementIndex.elementType
		};

		Craft.postActionRequest('elementIndexSettings/getCustomizeSourcesModalData', data, $.proxy(function(response, textStatus)
		{
			this.$loadingSpinner.remove();

			if (textStatus == 'success')
			{
				this.$saveBtn.removeClass('disabled');
				this.buildModal(response);
			}

		}, this));

		this.addListener(this.$newHeadingBtn, 'click', 'handleNewHeadingBtnClick');
		this.addListener(this.$cancelBtn, 'click', 'hide');
		this.addListener(this.$saveBtn, 'click', 'save');
		this.addListener(this.$container, 'submit', 'save');
	},

	buildModal: function(response)
	{
		// Store the available table attribute options
		this.availableTableAttributes = response.availableTableAttributes;

		// Create the source item sorter
		this.sourceSort = new Garnish.DragSort({
			handle: '.move',
			axis: 'y'
		});

		// Create the sources
		this.sources = [];

		for (var i = 0; i < response.sources.length; i++)
		{
			var source = this.addSource(response.sources[i]);
			this.sources.push(source);

			if (i === 0)
			{
				source.select();
			}
		}

		//this.addListener(this.$newBlockTypeBtn, 'click', 'addBlockType');
		//this.addListener(this.$newFieldBtn, 'click', 'addFieldToSelectedBlockType');
	},

	addSource: function(sourceData)
	{
		var $item = $('<div class="customize-sources-item"/>').appendTo(this.$sourcesContainer),
			$itemLabel = $('<div class="label"/>').appendTo($item),
			$itemInput = $('<input type="hidden"/>').appendTo($item),
			$moveHandle = $('<a class="move icon" title="'+Craft.t('Reorder')+'" role="button"></a>').appendTo($item),
			source;

		// Is this a heading?
		if (typeof sourceData.heading !== typeof undefined)
		{
			$item.addClass('heading');
			$itemInput.attr('name', 'sourceOrder[][heading]');
			source = new Craft.CustomizeSourcesModal.Heading(this, $item, $itemLabel, $itemInput, sourceData);
			source.updateItemLabel(sourceData.heading);
		}
		else
		{
			$itemInput.attr('name', 'sourceOrder[][key]').val(sourceData.key);
			source = new Craft.CustomizeSourcesModal.Source(this, $item, $itemLabel, $itemInput, sourceData);
			source.updateItemLabel(sourceData.label);
		}

		this.sourceSort.addItems($item);

		return source;
	},

	handleNewHeadingBtnClick: function()
	{
		var source = this.addSource({
			heading: ''
		});

		Garnish.scrollContainerToElement(this.$sidebar, source.$item);

		source.select();
	},

	save: function(ev)
	{
		if (ev)
		{
			ev.preventDefault();
		}

		if (this.$saveBtn.hasClass('disabled') || !this.$saveSpinner.hasClass('hidden'))
		{
			return;
		}

		this.$saveSpinner.removeClass('hidden');
		var data = this.$container.serialize()+'&elementType='+this.elementIndex.elementType;

		Craft.postActionRequest('elementIndexSettings/saveCustomizeSourcesModalSettings', data, $.proxy(function(response, textStatus)
		{
			if (textStatus == 'success' && response.success)
			{
				location.reload();
			}
			else
			{
				this.$saveSpinner.addClass('hidden');
				var error = (textStatus == 'success' && response.error ? response.error : Craft.t('An unknown error occurred.'));
				Craft.cp.displayError(error);
			}
		}, this));
	}
});

Craft.CustomizeSourcesModal.BaseSource = Garnish.Base.extend(
{
	modal: null,

	$item: null,
	$itemLabel: null,
	$itemInput: null,
	$settingsContainer: null,
	sourceData: null,

	init: function(modal, $item, $itemLabel, $itemInput, sourceData)
	{
		this.modal = modal;
		this.$item = $item;
		this.$itemLabel = $itemLabel;
		this.$itemInput = $itemInput;
		this.sourceData = sourceData;

		this.addListener(this.$item, 'click', 'select');
	},

	isSelected: function()
	{
		return (this.modal.selectedSource == this);
	},

	select: function()
	{
		if (this.isSelected())
		{
			return;
		}

		if (this.modal.selectedSource)
		{
			this.modal.selectedSource.deselect();
		}

		this.$item.addClass('sel');
		this.modal.selectedSource = this;

		if (!this.$settingsContainer)
		{
			this.$settingsContainer = $('<div/>')
				.append(this.createSettings())
				.appendTo(this.modal.$sourceSettingsContainer);
		}
		else
		{
			this.$settingsContainer.removeClass('hidden');
		}
	},

	createSettings: function()
	{
	},

	deselect: function()
	{
		this.$item.removeClass('sel');
		this.modal.selectedSource = null;
		this.$settingsContainer.addClass('hidden');
	},

	updateItemLabel: function(val)
	{
		this.$itemLabel.text(val);
	}
});

Craft.CustomizeSourcesModal.Source = Craft.CustomizeSourcesModal.BaseSource.extend(
{
	createSettings: function()
	{
		if (this.sourceData.tableAttributes.length)
		{
			// Create the title column option
			var firstAttribute = this.sourceData.tableAttributes[0],
				firstKey = firstAttribute[0],
				firstLabel = firstAttribute[1],
				$titleColumnCheckbox = this.createTableColumnOption(firstKey, firstLabel, true, true);

			// Create the rest of the options
			var $columnCheckboxes = $('<div/>'),
				selectedAttributes = [firstKey];

			$('<input type="hidden" name="sources['+this.sourceData.key+'][tableAttributes][]" value=""/>').appendTo($columnCheckboxes);

			// Add the selected columns, in the selected order
			for (var i = 1; i < this.sourceData.tableAttributes.length; i++)
			{
				var attribute = this.sourceData.tableAttributes[i],
					key = attribute[0],
					label = attribute[1];

				$columnCheckboxes.append(this.createTableColumnOption(key, label, false, true));
				selectedAttributes.push(key);
			}

			// Add the rest
			for (var i = 0; i < this.modal.availableTableAttributes.length; i++)
			{
				var attribute = this.modal.availableTableAttributes[i],
					key = attribute[0],
					label = attribute[1];

				if (!Craft.inArray(key, selectedAttributes))
				{
					$columnCheckboxes.append(this.createTableColumnOption(key, label, false, false));
				}
			}

			new Garnish.DragSort($columnCheckboxes.children(), {
				handle: '.move',
				axis: 'y'
			});

			return Craft.ui.createField($([$titleColumnCheckbox[0], $columnCheckboxes[0]]), {
				label: Craft.t('Table Columns'),
				instructions: Craft.t('Choose which table columns should be visible for this source, and in which order.')
			});
		}
	},

	createTableColumnOption: function(key, label, first, checked)
	{
		$option = $('<div class="customize-sources-table-column"/>')
		.append('<div class="icon move"/>')
		.append(
			Craft.ui.createCheckbox({
				label: label,
				name: 'sources['+this.sourceData.key+'][tableAttributes][]',
				value: key,
				checked: checked,
				disabled: first
			})
		);

		if (first)
		{
			$option.children('.move').addClass('disabled');
		}

		return $option;
	}
});

Craft.CustomizeSourcesModal.Heading = Craft.CustomizeSourcesModal.BaseSource.extend(
{
	$labelField: null,
	$labelInput: null,
	$deleteBtn: null,

	select: function()
	{
		this.base();
		this.$labelInput.focus();
	},

	createSettings: function()
	{
		this.$labelField = Craft.ui.createTextField({
			label: Craft.t('Heading'),
			instructions: Craft.t('This can be left blank if you just want an unlabeled separator.'),
			value: this.sourceData.heading
		});

		this.$labelInput = this.$labelField.find('.text');

		this.$deleteBtn = $('<a class="error delete"/>').text(Craft.t('Delete heading'));

		this.addListener(this.$labelInput, 'textchange', 'handleLabelInputChange');
		this.addListener(this.$deleteBtn, 'click', 'deleteHeading');

		return $([
			this.$labelField[0],
			$('<hr/>')[0],
			this.$deleteBtn[0]
		]);
	},

	handleLabelInputChange: function()
	{
		this.updateItemLabel(this.$labelInput.val());
	},

	updateItemLabel: function(val)
	{
		this.$itemLabel.html((val ? Craft.escapeHtml(val) : '<em class="light">'+Craft.t('(blank)')+'</em>')+'&nbsp;');
		this.$itemInput.val(val);
	},

	deleteHeading: function()
	{
		this.modal.sourceSort.removeItems(this.$item);

		this.modal.sources.splice($.inArray(this, this.modal.sources), 1);

		if (this.isSelected())
		{
			this.deselect();

			if (this.modal.sources.length)
			{
				this.modal.sources[0].select();
			}
		}

		this.$item.remove();
		this.$settingsContainer.remove();
		this.destroy();
	}
});