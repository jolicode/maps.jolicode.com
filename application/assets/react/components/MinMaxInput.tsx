import React from 'react';

function MinMaxInput({
  onChange,
  min,
  max,
}: {
  min?: number;
  max?: number;
  onChange: (value: number) => void;
}) {
  return (
    <div className="mb-2 border border-gray-200 rounded p-2">
      <div className="flex justify-between items-center mb-1">
        <label className="block text-sm">Min / Max Zoom</label>
        <div>
          <input
            type="number"
            value={min}
            className="w-20 h-8 rounded-lg appearance-none border border-gray-300 mr-2 p-2"
            onChange={(e) => onChange(Number(e.target.value))}
            placeholder="Min"
          />
          <input
            type="number"
            value={max}
            className="w-20 h-8 rounded-lg appearance-none border border-gray-300 p-2"
            onChange={(e) => onChange(Number(e.target.value))}
            placeholder="Max"
          />
        </div>
      </div>
    </div>
  );
}

export default MinMaxInput;
