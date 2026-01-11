import React from 'react';
import CodeArea from './CodeArea';
import { DataDrivenPropertyValueSpecification } from 'maplibre-gl';
import CodeSwitchButton from './CodeSwitchButton';

function RangeInput({
  onChange,
  value,
  title,
  min,
  max,
  step,
}: {
  value?: DataDrivenPropertyValueSpecification<number>;
  onChange: (value: DataDrivenPropertyValueSpecification<number> | string) => void;
  title?: string;
  min?: number;
  max?: number;
  step?: number;
}) {
  const [isNumberMode, setIsNumberMode] = React.useState(typeof value === 'number');

  return (
    <div className="mb-2 border border-gray-200 rounded p-2">
      <div className="flex justify-between items-center mb-1">
        <label className="block text-sm">
          {title} {isNumberMode && typeof value === 'number' ? `- ${value}` : ''}
        </label>
        <CodeSwitchButton mode={isNumberMode} setMode={setIsNumberMode} />
      </div>
      {isNumberMode ? (
        <input
          id="default-range"
          type="range"
          value={Number(value) || 0}
          className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
          onChange={(e) => onChange(Number(e.target.value))}
          min={min}
          max={max}
          step={step}
        />
      ) : (
        <CodeArea value={JSON.stringify(value, null, 1)} onChange={onChange} />
      )}
    </div>
  );
}

export default RangeInput;
