import Button from '@/shared/ui/Button';
import { transitionAction } from '../lib/presentation';

/** Una acción visible por transición permitida (las calcula el dominio, no el front). */
export default function StatusActions({ incident, onTransition, disabled }) {
    return (
        <div className="flex flex-wrap gap-1.5">
            {incident.transitions.map((t) => {
                const { verb, variant } = transitionAction(incident.status.value, t.value);
                return (
                    <Button key={t.value} size="sm" variant={variant} disabled={disabled} onClick={() => onTransition(incident, t)} title={`Pasar a «${t.label}»`}>
                        {verb}
                    </Button>
                );
            })}
        </div>
    );
}
