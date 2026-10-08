import OperatorsManager from '@/features/operators/OperatorsManager';

/** Página Inertia (adaptador de ruta): solo entrega las props del servidor al feature. */
export default function Index(props) {
    return <OperatorsManager {...props} />;
}
