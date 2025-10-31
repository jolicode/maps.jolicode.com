import React from 'react';
import { Sketch } from '@uiw/react-color';
import { DataDrivenPropertyValueSpecification, ColorSpecification } from 'maplibre-gl';
import CodeArea from './CodeArea';
import CodeSwitchButton from './CodeSwitchButton';

function ColorPicker({
  onChange,
  color,
  title,
}: {
  color?: DataDrivenPropertyValueSpecification<ColorSpecification>;
  onChange: (color: DataDrivenPropertyValueSpecification<ColorSpecification>) => void;
  title?: string;
}) {
  const [displayColorPicker, setDisplayColorPicker] = React.useState(false);
  const [isColorPickerMode, setColorPickerMode] = React.useState(
    typeof color === 'string' && !Array.isArray(color)
  );

  const handleClick = () => {
    setDisplayColorPicker(!displayColorPicker);
  };

  const handleClose = () => {
    setDisplayColorPicker(false);
  };

  return (
    <div className="relative mb-2">
      <div className="p-2 border border-gray-200 rounded">
        <div className="flex justify-between items-center mb-2">
          {title && (
            <p className="text-sm">
              {title}{' '}
              {typeof color === 'string' ? <span style={{ color: color }}>{color}</span> : ''}
            </p>
          )}
          <CodeSwitchButton
            mode={isColorPickerMode}
            setMode={setColorPickerMode}
            inputTypeName="Color Picker"
          />
        </div>
        {isColorPickerMode ? (
          <button
            onClick={handleClick}
            className="h-6 w-full rounded border border-white hover:border-gray-400 cursor-pointer"
            style={{ backgroundColor: `${color}` }}
          ></button>
        ) : (
          <CodeArea value={JSON.stringify(color, null, 1)} onChange={onChange} />
        )}
      </div>
      {typeof color === 'string' && displayColorPicker ? (
        <div className="absolute z-10">
          <div className="fixed top-0 right-0 bottom-0 left-0" onClick={handleClose} />
          <Sketch onChange={(e) => onChange(e.hex)} color={color} />
        </div>
      ) : null}
    </div>
  );
}

export default ColorPicker;
