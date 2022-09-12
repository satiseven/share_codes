import { DateTime } from 'luxon'
import { BaseModel, BelongsTo, belongsTo, column } from '@ioc:Adonis/Lucid/Orm'
import User from './User'
import PingSequence from './PingSequence'

export default class PingSequenceDetail extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  // TODO: rename pingname to name after the data migration
  @column()
  public pingName: string

  @column()
  public day: number

  @column()
  public step: number

  @column()
  public html: string

  @column()
  public isDeleted: boolean = false

  @column()
  public pingSequenceId: string

  @column()
  public userId: string

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  // TODO: check if this is necessary at all
  // It exists in the old codebase, not sure where it's used.
  @belongsTo(() => User)
  public user: BelongsTo<typeof User>

  @belongsTo(() => PingSequence)
  public pingSequence: BelongsTo<typeof PingSequence>

  public get serializedPingSequenceDetailInfo() {
    const { id, day, step, html, pingSequenceId, userId, isDeleted } = this

    return {
      id,
      day,
      step,
      html,
      pingSequenceId,
      userId,
      isDeleted,
    }
  }
}
