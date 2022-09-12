import { DateTime } from 'luxon'
import { BaseModel, BelongsTo, belongsTo, column } from '@ioc:Adonis/Lucid/Orm'
import Signature from './Signature'

// SignatureEvent literally holds nothing we can't derive from the related email row.
// We could remove all columns except emailId, and created_at. Nothing would change for the applicaton

export default class SignatureEvent extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public signatureId: string

  @column()
  public userId: string

  @column()
  public emailId: string

  @column()
  public emailSubject: string | null

  @column()
  public clickRecipient: string

  @column()
  public isDeleted: boolean = false

  // TODO: Fix typo on signature_cliked_time. rename this column after migrating the old database
  // TODO: Also this column has the same value with created_at. This can be a computed getter on model that
  // returns createdAt
  @column.dateTime({ autoCreate: true })
  public signatureClikedTime: DateTime

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  @belongsTo(() => Signature)
  public signature: BelongsTo<typeof Signature>
}
