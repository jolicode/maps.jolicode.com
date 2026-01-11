import React, { useState } from 'react';
import CodeEditor from '@uiw/react-textarea-code-editor';
// import { ColorSpecification, DataDrivenPropertyValueSpecification } from 'maplibre-gl';

function CodeArea({ onChange, value }: { value?: string; onChange: (value: any) => void }) {
  const [code, setCode] = useState(value || '');
  const [isValid, setIsValid] = useState(true);

  return (
    <CodeEditor
      value={code}
      language="json"
      onChange={(evn) => {
        let parsed;

        // check if json is valid
        try {
          parsed = JSON.parse(evn.target.value);
        } catch (e) {
          setIsValid(false);
          setCode(evn.target.value);
          return;
        }

        setIsValid(true);
        setCode(evn.target.value);
        onChange(parsed);
      }}
      className={!isValid ? 'border border-red-500 rounded' : ''}
    />
  );
}

export default CodeArea;
