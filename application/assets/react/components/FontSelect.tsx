import React from 'react';
import SelectInput from './SelectInput';

function FontSelect({
  onChange,
  value,
}: {
  value?: string;
  onChange: (value: React.ChangeEvent<HTMLSelectElement>) => void;
}) {
  return (
    <SelectInput
      title="Text Font"
      value={value || '["Noto Sans Italic"]'}
      onChange={onChange}
      options={[
        { label: 'Noto Sans Italic', value: 'Noto Sans Italic' },
        { label: 'Noto Sans Regular', value: 'Noto Sans Regular' },
        { label: 'Noto Sans Medium', value: 'Noto Sans Medium' },
        {
          label: 'Noto Sans Bold',
          value: 'Noto Sans Bold',
        },
        { label: 'Source Sans 3 Regular', value: 'Source Sans 3 Regular' },
        {
          label: 'Darker Grotesque Medium',
          value: 'Darker Grotesque Medium',
        },
      ]}
    />
  );
}

export default FontSelect;
