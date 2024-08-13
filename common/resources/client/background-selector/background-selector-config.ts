import {MessageDescriptor} from '@common/i18n/message-descriptor';

export interface BackgroundSelectorConfig {
  type: 'image' | 'gradient' | 'color';
  id: string;
  backgroundColor?: string;
  backgroundAttachment?: string;
  backgroundSize?: string;
  backgroundRepeat?: string;
  backgroundPosition?: string;
  backgroundImage?: string;
  color?: string;
  label: MessageDescriptor;
}
