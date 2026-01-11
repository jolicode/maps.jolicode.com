import React from 'react';

function LayerTypeIcon({ type }: { type: string }) {
  if (type === 'fill') {
    return (
      <svg
        className="text-gray-200"
        xmlns="http://www.w3.org/2000/svg"
        width="24"
        height="24"
        viewBox="0 0 24 24"
      >
        <path
          fill="currentColor"
          d="M19 11.5s-2 2.17-2 3.5a2 2 0 0 0 2 2a2 2 0 0 0 2-2c0-1.33-2-3.5-2-3.5M5.21 10L10 5.21L14.79 10m1.77-1.06L7.62 0L6.21 1.41l2.38 2.38l-5.15 5.15c-.59.56-.59 1.53 0 2.12l5.5 5.5c.29.29.68.44 1.06.44s.77-.15 1.06-.44l5.5-5.5c.59-.59.59-1.56 0-2.12"
        />
      </svg>
    );
  }

  if (type === 'symbol') {
    return (
      <svg
        className="text-gray-200"
        xmlns="http://www.w3.org/2000/svg"
        width="24"
        height="24"
        viewBox="0 0 24 24"
      >
        <g fill="none">
          <path d="M0 0h24v24H0z" />
          <path
            fill="currentColor"
            d="M5 3.5a1.5 1.5 0 1 0 0 3h5.5V20a1.5 1.5 0 0 0 3 0V6.5H19a1.5 1.5 0 0 0 0-3z"
          />
        </g>
      </svg>
    );
  }

  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
      className="text-gray-200"
    >
      <path
        fill="currentColor"
        d="M2.75 17.75q-.325-.325-.325-.75t.325-.75l5.325-5.325q.575-.575 1.425-.575t1.425.575L13.5 13.5l6.4-7.225q.275-.325.713-.325t.737.3q.275.275.287.662t-.262.688L14.9 14.9q-.575.65-1.425.688T12 15l-2.5-2.5l-5.25 5.25q-.325.325-.75.325t-.75-.325"
      />
    </svg>
  );
}

export default LayerTypeIcon;
