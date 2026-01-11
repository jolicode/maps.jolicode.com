import React from 'react';
import LayerTypeIcon from './LayerTypeIcon';

function Collapse({
  children,
  layerType,
  layerId,
}: {
  children: React.ReactNode;
  layerType: string;
  layerId: string;
}) {
  const [isOpen, setIsOpen] = React.useState(false);

  return (
    <>
      <div
        className="flex justify-between items-center mb-2 cursor-pointer"
        onClick={() => setIsOpen(!isOpen)}
      >
        <p className="text-medium flex items-center gap-1">
          <LayerTypeIcon type={layerType} />
          {layerId}
        </p>
        <button className={isOpen ? 'rotate-180 transition-transform' : 'transition-transform'}>
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
            <path
              fill="none"
              stroke="currentColor"
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              d="m6 15l6-6l6 6"
            />
          </svg>
        </button>
      </div>
      {isOpen && <div className="mb-2">{children}</div>}
    </>
  );
}

export default Collapse;
