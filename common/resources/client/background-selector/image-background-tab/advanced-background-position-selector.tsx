import {message} from '@common/i18n/message';
import {BgSelectorTabProps} from '@common/background-selector/bg-selector-tab-props';
import {BackgroundSelectorConfig} from '@common/background-selector/background-selector-config';
import {Trans} from '@common/i18n/trans';
import {RadioGroup} from '@common/ui/forms/radio-group/radio-group';
import {Radio} from '@common/ui/forms/radio-group/radio';
import {ButtonBase} from '@common/ui/buttons/button-base';
import clsx from 'clsx';

const repeat = [
  {
    value: 'no-repeat',
    label: message("Don't repeat"),
  },
  {
    value: 'repeat-x',
    label: message('Horizontal'),
  },
  {
    value: 'repeat-y',
    label: message('Vertical'),
  },
  {
    value: 'repeat',
    label: message('Both'),
  },
];

const size = [
  {
    value: 'auto',
    label: message('Auto'),
  },
  {
    value: 'cover',
    label: message('Stretch to fit'),
  },
  {
    value: 'contain',
    label: message('Fit image'),
  },
];

const position = [
  'left top',
  'center top',
  'right top',
  'left center',
  'center center',
  'right center',
  'left bottom',
  'center bottom',
  'right bottom',
];

export function AdvancedBackgroundPositionSelector({
  value,
  onChange,
}: BgSelectorTabProps<BackgroundSelectorConfig>) {
  return (
    <div className="mt-14 flex gap-60 border-t pt-14">
      <RepeatSelector value={value} onChange={onChange} />
      <SizeSelector value={value} onChange={onChange} />
      <PositionSelector value={value} onChange={onChange} />
    </div>
  );
}

function RepeatSelector({
  value,
  onChange,
}: BgSelectorTabProps<BackgroundSelectorConfig>) {
  return (
    <div>
      <div className="mb-10">
        <Trans message="Repeat" />
      </div>
      <RadioGroup orientation="vertical" size="sm" disabled={!value}>
        {repeat.map(({value: repeatValue, label}) => (
          <Radio
            key={repeatValue}
            value={repeatValue}
            checked={value?.backgroundRepeat === repeatValue}
            onChange={() => {
              onChange?.({
                ...value!,
                backgroundRepeat: repeatValue,
              });
            }}
          >
            <Trans {...label} />
          </Radio>
        ))}
      </RadioGroup>
    </div>
  );
}

function SizeSelector({
  value,
  onChange,
}: BgSelectorTabProps<BackgroundSelectorConfig>) {
  return (
    <div>
      <div className="mb-10">
        <Trans message="Size" />
      </div>
      <RadioGroup orientation="vertical" size="sm" disabled={!value}>
        {size.map(({value: sizeValue, label}) => (
          <Radio
            key={sizeValue}
            value={sizeValue}
            checked={value?.backgroundSize === sizeValue}
            onChange={() => {
              onChange?.({
                ...value!,
                backgroundSize: sizeValue,
              });
            }}
          >
            <Trans {...label} />
          </Radio>
        ))}
      </RadioGroup>
    </div>
  );
}

function PositionSelector({
  value,
  onChange,
}: BgSelectorTabProps<BackgroundSelectorConfig>) {
  return (
    <div>
      <div className="mb-10">
        <Trans message="Position" />
      </div>
      <div className="grid grid-cols-3 gap-8">
        {position.map(position => (
          <PositionSelectorButton
            disabled={!value}
            key={position}
            value={value}
            onChange={onChange}
            position={position}
          />
        ))}
      </div>
    </div>
  );
}

interface PositionSelectorButtonProps {
  value: BackgroundSelectorConfig | undefined;
  onChange: (value: BackgroundSelectorConfig) => void;
  position: string;
  disabled: boolean;
}
function PositionSelectorButton({
  value,
  onChange,
  position,
  disabled,
}: PositionSelectorButtonProps) {
  return (
    <ButtonBase
      disabled={disabled}
      onClick={() => {
        onChange({
          ...value!,
          backgroundPosition: position,
        });
      }}
      className={clsx(
        'h-26 w-26 rounded border',
        value?.backgroundPosition === position ? 'bg-primary' : 'bg-alt',
      )}
    />
  );
}
