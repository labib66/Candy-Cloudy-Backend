import {BackgroundSelectorConfig} from './background-selector-config';

interface ReturnedProps {
  backgroundImage?: string;
  backgroundColor?: string;
  backgroundAttachment?: string;
  backgroundSize?: string;
  backgroundRepeat?: string;
  backgroundPosition?: string;
  color?: string;
}

export function cssPropsFromBgConfig(
  bgConfig?: Partial<BackgroundSelectorConfig>,
): ReturnedProps | undefined {
  if (bgConfig) {
    return {
      backgroundImage: bgConfig.backgroundImage,
      backgroundColor: bgConfig.backgroundColor,
      backgroundAttachment: bgConfig.backgroundAttachment,
      backgroundSize: bgConfig.backgroundSize,
      backgroundRepeat: bgConfig.backgroundRepeat,
      backgroundPosition: bgConfig.backgroundPosition,
      color: bgConfig.color,
    };
  }
}
