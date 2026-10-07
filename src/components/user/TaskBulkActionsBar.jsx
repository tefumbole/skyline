import React from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Trash2 } from 'lucide-react';

const TaskBulkActionsBar = ({
  totalCount,
  selectedIds,
  onSelectAll,
  onClearSelection,
  onDeleteSelected,
  deleteLabel = 'Delete Selected',
  deleting = false,
}) => {
  const allSelected = totalCount > 0 && selectedIds.length === totalCount;
  const someSelected = selectedIds.length > 0;

  return (
    <div className="flex flex-wrap items-center gap-3 bg-white p-3 rounded-lg shadow-sm border">
      <label className="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
        <Checkbox
          checked={allSelected}
          onCheckedChange={(checked) => onSelectAll(Boolean(checked))}
          aria-label="Select all tasks"
        />
        Select all
      </label>

      {someSelected && (
        <>
          <span className="text-sm text-gray-500">
            {selectedIds.length} selected
          </span>
          <Button
            type="button"
            variant="outline"
            size="sm"
            className="text-red-600 border-red-200 hover:bg-red-50"
            disabled={deleting}
            onClick={() => onDeleteSelected()}
          >
            <Trash2 className="w-4 h-4 mr-2" />
            {deleting ? 'Removing...' : deleteLabel}
          </Button>
          <Button type="button" variant="ghost" size="sm" onClick={onClearSelection}>
            Clear
          </Button>
        </>
      )}
    </div>
  );
};

export default TaskBulkActionsBar;
