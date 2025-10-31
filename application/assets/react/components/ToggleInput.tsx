import React from 'react';

function ToggleInput({
  onChange,
  value,
}: {
  value?: 'visible' | 'none';
  onChange: (value: React.ChangeEvent<HTMLInputElement>) => void;
}) {
  return (
    <div className="border border-gray-200 hover:bg-gray-200 rounded p-2 mb-2">
      <label className="flex items-center justify-between cursor-pointer">
        <span className="me-3 text-sm">Visibility</span>
        <div>
          <input
            type="checkbox"
            checked={value === 'visible' || !value}
            className="hidden peer"
            onChange={onChange}
          />
          <div
            className="relative w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-200 rounded-full
                      peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full
                      peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px]
                      after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all
                      peer-checked:bg-blue-400"
          ></div>
        </div>
      </label>
    </div>
  );
}

export default ToggleInput;
