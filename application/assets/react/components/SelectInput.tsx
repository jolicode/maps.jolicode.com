import React from 'react';

function SelectInput({
  onChange,
  value,
  options,
  title,
}: {
  value?: string;
  onChange: (value: React.ChangeEvent<HTMLSelectElement>) => void;
  options: Array<{ label: string; value: string }>;
  title?: string;
}) {
  return (
    <div className="border border-gray-200 rounded p-2 mb-2">
      <div className="flex justify-between items-center">
        <label className="block text-sm">{title}</label>
        <select
          name="line-style"
          className="border border-gray-200 rounded p-1 text-xs"
          value={value}
          onChange={onChange}
        >
          {options.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      </div>
    </div>
  );
}

export default SelectInput;
