import { ColorSpecification, LayerSpecification, StyleSpecification } from 'maplibre-gl';
import { DataDrivenPropertyValueSpecification } from 'maplibre-gl';

export const updateMapStyle = (
  mapStyle: StyleSpecification,
  id: string,
  propertyName: 'minzoom' | 'maxzoom' | string,
  category: 'paint' | 'layout' | 'root',
  value:
    | DataDrivenPropertyValueSpecification<number>
    | string
    | number
    | DataDrivenPropertyValueSpecification<number[]>
    | DataDrivenPropertyValueSpecification<ColorSpecification>
) => {
  const updatedMapStyle = { ...mapStyle };

  updatedMapStyle.layers.forEach((layer) => {
    if (layer.id === id) {
      if (category === 'root') {
        if (propertyName === 'minzoom' || propertyName === 'maxzoom') {
          layer[propertyName] = Number(value);
          return;
        }

        return;
      }

      layer[category] = {
        ...(layer[category] || {}),
        [propertyName]: value,
      };
    }
  });

  return updatedMapStyle;
};

export const getLayerColor = (layer?: LayerSpecification, layerId?: string) => {
  if (!layerId || !layer) return;

  if (layer.type === 'fill') {
    return layer.paint?.['fill-color'];
  } else if (layer.type === 'line') {
    return layer.paint?.['line-color'];
  } else if (layer.type === 'circle') {
    return layer.paint?.['circle-color'];
  } else if (layer.type === 'background') {
    return layer.paint?.['background-color'];
  } else if (layer.type === 'symbol') {
    return layer.paint?.['text-color'];
  }
};

export const getColorPropertyName = (layerType: string) => {
  switch (layerType) {
    case 'fill':
      return 'fill-color';
    case 'line':
      return 'line-color';
    case 'circle':
      return 'circle-color';
    case 'background':
      return 'background-color';
    case 'symbol':
      return 'text-color';
    default:
      return 'fill-color';
  }
};

export const exportJsonEvent = (mapStyle: maplibregl.StyleSpecification) => {
  const exportButton = document.querySelector('#export-button') as HTMLButtonElement;
  const exportInput = document.querySelector('#export-input') as HTMLInputElement;

  if (exportButton && exportInput) {
    exportButton.addEventListener('click', () => {
      const styleString = JSON.stringify(mapStyle, null, 2);
      const blob = new Blob([styleString], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = exportInput.value || 'map-style.json';
      a.click();
      URL.revokeObjectURL(url);
    });
  }
};
