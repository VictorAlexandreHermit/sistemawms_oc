-- Migration 000003: Rastreio da quantidade guardada (putaway) por item do pedido.

ALTER TABLE pedido_itens
    ADD COLUMN quantidade_guardada INT NOT NULL DEFAULT 0 AFTER quantidade_bipada_picking;