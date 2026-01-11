import * as React from 'react';
import { Layer, Map, MapProvider, Source } from '@vis.gl/react-maplibre';
import 'maplibre-gl/dist/maplibre-gl.css'; // See notes below
import {
  CircleLayerSpecification,
  MapGeoJSONFeature,
  default as maplibregl,
  StyleSpecification,
} from 'maplibre-gl';
import * as pmtiles from 'pmtiles';
import {
  exportJsonEvent,
  getColorPropertyName,
  getLayerColor,
  loadSavedStyle,
  saveStyleEvent,
  updateMapStyle,
} from '../utils';
import ColorPicker from '../components/ColorPicker';
import RangeInput from '../components/RangeInput';
import LayerTypeIcon from '../components/LayerTypeIcon';
import SelectInput from '../components/SelectInput';
import ToggleInput from '../components/ToggleInput';
import FontSelect from '../components/FontSelect';
import Collapse from '../components/Collapse';
import MinMaxInput from '../components/MinMaxInput';

const layerStyle: CircleLayerSpecification = {
  id: 'point',
  type: 'circle',
  source: 'point',
  paint: {
    'circle-radius': 10,
    'circle-color': '#fb69b08d',
  },
};

const MapComponent = (props: { styleUrl: string; style: string }) => {
  const url = '/style/' + props.styleUrl + '.json';
  const [mapStyle, setMapStyle] = React.useState<StyleSpecification | null>(null);
  const [viewState, setViewState] = React.useState({
    longitude: 2.337,
    latitude: 48.87,
    zoom: 14.3,
  });
  const [layerSearchTerm, setLayerSearchTerm] = React.useState<string>('');
  const [features, setFeatures] = React.useState<Array<MapGeoJSONFeature>>([]);
  const [mousePosition, setMousePosition] = React.useState<number[] | null>(null);

  React.useEffect(() => {
    const hash = document.location.hash.substring(1);

    if (hash) {
      const parts = hash.split('/');

      if (parts.length === 3) {
        const [zoom, longitude, latitude] = parts;
        setViewState({
          zoom: parseFloat(zoom),
          longitude: parseFloat(longitude),
          latitude: parseFloat(latitude),
        });
      }
    }

    // add the PMTiles plugin to the maplibregl global.
    const protocol = new pmtiles.Protocol();
    maplibregl.addProtocol('pmtiles', protocol.tile);

    // this is so we share one instance across the JS code and the map renderer
    protocol.add(new pmtiles.PMTiles('/pmtiles/{{ location }}.pmtiles'));

    fetch(url)
      .then(async (response) => {
        const style = await response.json();
        setMapStyle(style);

        exportJsonEvent(style);
        saveStyleEvent(style);
      })
      .catch((error) => {
        console.error('Error fetching map style:', error);
        return null;
      });
  }, []);

  if (!mapStyle) {
    return <div>Loading style...</div>;
  }

  return (
    <>
      <Map
        {...viewState}
        onMove={(evt) => setViewState(evt.viewState)}
        minZoom={1}
        maxZoom={19}
        style={{ width: '100%', height: '94vh' }}
        mapStyle={{
          ...mapStyle,
          glyphs: '/build/fonts/{fontstack}/{range}.pbf',
          sprite: 'https://jolimap.test/build/sprite/v4/' + props.style + '',
        }}
        onClick={(e) => {
          const { x, y } = e.point;
          const r = 2;
          let features = e.target.queryRenderedFeatures([
            [x - r, y - r],
            [x + r, y + r],
          ]);

          setFeatures(features);
          setMousePosition([e.lngLat.lng, e.lngLat.lat]);
        }}
        cursor="crosshair"
      >
        <Source
          id="point"
          type="geojson"
          data={{
            type: 'Point' as const,
            coordinates: mousePosition || [2.337, 48.87],
          }}
        >
          <Layer {...layerStyle} />
        </Source>
      </Map>
      <div className="flex flex-col gap-2 h-full min-w-84 max-w-96">
        <div className="flex-shrink-0 bg-white border border-gray-200 m-2 p-2 rounded-lg">
          <p>Longitude: {viewState.longitude}</p>
          <p>Latitude: {viewState.latitude}</p>
          <p>Zoom: {viewState.zoom}</p>
          <input
            type="text"
            id="search-layers"
            placeholder="Search layers..."
            className="border border-gray-200 rounded p-1 mt-1 mb-2 w-full text-sm"
            onChange={(e) => {
              const searchTerm = e.target.value.toLowerCase();
              setLayerSearchTerm(searchTerm);
            }}
          />
        </div>
        {!!localStorage.getItem('myStyle') && (
          <div className="flex-shrink-0 bg-white border border-gray-200 mx-2 p-2 rounded-lg">
            <button
              onClick={() => {
                const savedStyle = loadSavedStyle();
                if (savedStyle) {
                  setMapStyle(savedStyle);
                }
              }}
              className="rounded-lg underline cursor-pointer"
            >
              Load saved style
            </button>
          </div>
        )}

        <div className="overflow-y-auto flex-shrink-1 h-full bg-white bg-opacity-60 p-2 rounded-lg">
          <h3 className="font-bold text-xl text-gray-700 mb-2">Global edits</h3>

          <ColorPicker
            title="Background Color"
            color={getLayerColor(
              mapStyle.layers.find((layer) => layer.id === 'background'),
              'background'
            )}
            onChange={(color) => {
              setMapStyle(
                updateMapStyle(mapStyle, 'background', 'background-color', 'paint', color)
              );
            }}
          />

          <h3 className="font-bold text-xl text-gray-700 mb-2">Layers</h3>
          {mapStyle?.layers
            .filter(
              (layer) =>
                layer.id !== 'background' &&
                layer.id.toLowerCase().includes(layerSearchTerm) &&
                (features.length === 0 || features.some((f) => f.layer.id === layer.id))
            )
            .map((layer) => {
              return (
                <div key={layer.id} className="pb-2">
                  <Collapse layerId={layer.id} layerType={layer.type}>
                    <ToggleInput
                      value={layer.layout?.visibility}
                      onChange={(e) => {
                        const visibility = e.target.checked ? 'visible' : 'none';
                        setMapStyle(
                          updateMapStyle(mapStyle, layer.id, 'visibility', 'layout', visibility)
                        );
                      }}
                    />
                    <MinMaxInput
                      min={layer.minzoom}
                      max={layer.maxzoom}
                      onChange={(value) => {
                        setMapStyle(updateMapStyle(mapStyle, layer.id, 'minzoom', 'root', value));
                        setMapStyle(updateMapStyle(mapStyle, layer.id, 'maxzoom', 'root', value));
                      }}
                    />
                    {['fill', 'line', 'circle', 'symbol'].includes(layer.type) && (
                      <ColorPicker
                        title={`Color`}
                        color={getLayerColor(layer, layer.id)}
                        onChange={(color) => {
                          setMapStyle(
                            updateMapStyle(
                              mapStyle,
                              layer.id,
                              getColorPropertyName(layer.type),
                              'paint',
                              color
                            )
                          );
                        }}
                      />
                    )}
                    {layer.type === 'fill' && (
                      <RangeInput
                        title="Fill Opacity"
                        value={layer.paint?.['fill-opacity'] || 1}
                        onChange={(value) => {
                          setMapStyle(
                            updateMapStyle(mapStyle, layer.id, 'fill-opacity', 'paint', value)
                          );
                        }}
                        max={1}
                        step={0.05}
                      />
                    )}
                    {layer.type === 'line' && (
                      <>
                        <RangeInput
                          title="Line Width"
                          value={layer.paint?.['line-width']}
                          onChange={(value) => {
                            setMapStyle(
                              updateMapStyle(mapStyle, layer.id, 'line-width', 'paint', value)
                            );
                          }}
                        />
                        <SelectInput
                          title={`Line Style ${layer.paint?.['line-dasharray']?.toString()}`}
                          value={
                            Array.isArray(layer.paint?.['line-dasharray']) &&
                            layer.paint?.['line-dasharray'].length > 0
                              ? 'dashed'
                              : 'solid'
                          }
                          onChange={(e) => {
                            const dashArray = e.target.value === 'dashed' ? [3, 2] : [];
                            setMapStyle(
                              updateMapStyle(
                                mapStyle,
                                layer.id,
                                'line-dasharray',
                                'paint',
                                dashArray
                              )
                            );
                          }}
                          options={[
                            { label: 'Solid', value: 'solid' },
                            { label: 'Dashed', value: 'dashed' },
                          ]}
                        />
                        <RangeInput
                          title="Line Opacity"
                          value={layer.paint?.['line-opacity'] || 1}
                          onChange={(value) => {
                            setMapStyle(
                              updateMapStyle(mapStyle, layer.id, 'line-opacity', 'paint', value)
                            );
                          }}
                          max={1}
                          step={0.05}
                        />
                      </>
                    )}
                    {layer.type === 'symbol' && (
                      <>
                        <FontSelect
                          value={layer.layout?.['text-font']?.toString()}
                          onChange={(value) => {
                            const parsedValue = JSON.parse('["' + value.target.value + '"]');
                            setMapStyle(
                              updateMapStyle(mapStyle, layer.id, 'text-font', 'layout', parsedValue)
                            );
                          }}
                        />
                        <RangeInput
                          title="Text Opacity"
                          value={layer.paint?.['text-opacity'] || 1}
                          onChange={(value) => {
                            setMapStyle(
                              updateMapStyle(mapStyle, layer.id, 'text-opacity', 'paint', value)
                            );
                          }}
                          max={1}
                          step={0.05}
                        />
                        <ColorPicker
                          title={`Text Outline Color`}
                          color={`${layer.paint?.['text-halo-color']}`}
                          onChange={(color) => {
                            setMapStyle(
                              updateMapStyle(mapStyle, layer.id, 'text-halo-color', 'paint', color)
                            );
                          }}
                        />
                        <RangeInput
                          title="Text Outline Width"
                          value={layer.paint?.['text-halo-width'] || 1}
                          onChange={(value) => {
                            setMapStyle(
                              updateMapStyle(mapStyle, layer.id, 'text-halo-width', 'paint', value)
                            );
                          }}
                          max={4}
                          step={0.1}
                        />
                      </>
                    )}
                  </Collapse>
                </div>
              );
            })}

          {/* <h3 className="font-bold mb-2">Features under mouse cursor:</h3>
          <div>Move the mouse over the map to see feature information here and click to edit.</div>
          <div>
            {features.map((feature, index) => {
              const currentStyle = mapStyle.layers.find((layer) => layer.id === feature.layer.id);
              if (currentStyle) {
                  return (
                    <div key={index} className="text-xs">
                      <p>{feature.sourceLayer}</p>
                      <pre>{JSON.stringify(feature.properties, null, 2)}</pre>
                      <pre>{JSON.stringify(feature.layer, null, 2)}</pre>
                      <pre>{currentStyle.id}</pre>
                    </div>
                  );

                return (
                  <div key={index} className="my-2">
                    <p className="pb-2">{feature.sourceLayer}</p>
                  </div>
                );
              }
            })}
          </div> */}
        </div>
      </div>
    </>
  );
};

export default function MapWrapper(props: { styleUrl: string; style: string }) {
  return (
    <MapProvider>
      <MapComponent {...props} />
    </MapProvider>
  );
}
